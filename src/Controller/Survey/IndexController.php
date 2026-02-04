<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-26
 * Time: 16:20
 */

namespace App\Controller\Survey;

use App\Controller\AbstractController;
use App\Entity\Survey\Category;
use App\Entity\Survey\Question;
use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveyQuestion;
use App\Entity\Survey\SurveyVoucher;
use App\Form\Survey\SurveyQuestionType;
use App\Form\Survey\SurveyType;
use App\Repository\Survey\CategoryRepository;
use App\Repository\Survey\QuestionRepository;
use App\Repository\Survey\SurveyQuestionRepository;
use App\Repository\Survey\SurveyRepository;
use App\Repository\Survey\SurveyVoucherRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Knp\Menu\MenuItem;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @Route("/survey", name="survey_")
 * @IsGranted("ROLE_USER")
 */
class IndexController extends AbstractController
{

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @Route("/", name="home")
     * @return Response
     * @throws \Exception
     */
    public function index(): Response
    {
        return $this->render('survey/index/index.html.twig', [
            'school' => $this->getUser()->getCurrentSchool()
        ]);
    }

    /**
     * @Route("/new/{uuid}", name="new", defaults={"uuid": null})
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param Request $request
     * @param string|null $uuid
     * @return Response
     * @throws ConnectionException
     */
    public function new(Request $request, ?string $uuid): Response
    {
        $em = $this->getDoctrine()->getManager();
        $survey = new Survey();
        $templateSurvey = null;

        $form = $this->createForm(SurveyType::class, $survey, [
            'new' => true,
            'isAdmin' => \in_array('ROLE_ADMIN', $this->getUser()->getRoles()),
            'template' => $uuid ? $uuid : null
        ]);
        $form->handleRequest($request);
        if (! \is_null($uuid)) {
            try {
                $templateSurvey = $em->getRepository(Survey::class)->findOneBy(['uuid' => $uuid]);

                if (\is_null($templateSurvey)) {
                    $form->get('template')->addError(new FormError('Template nicht gefunden!'));
                }
            } catch (\Throwable $e) {
                $form->get('template')->addError(new FormError($e->getMessage()));
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();
            $conn->beginTransaction();
            try {
                $survey->setCreatedBy($this->getUser());
                $survey->setSchool($this->getUser()->getCurrentSchool());
                if (! \is_null($templateSurvey)) {
                    $order = 1;
                    foreach ($templateSurvey->getQuestions() as $question) {
                        $questionClone = clone $question;
                        $questionClone->setOrder($order);
                        $questionClone->setSurvey($survey);
                        $survey->getQuestions()->add($questionClone);
                        $order++;
                    }
                }
                $em->persist($survey);
                $em->flush($survey);

                if ($survey->getType() === Survey::TYPE_VOUCHER) {
                    $this->createVoucher($survey, $survey->getNumberOfVoucher());
                }

                $conn->commit();
                $this->getSuccessMessage();

                return $this->redirectToRoute('survey_questions', ['id' => $survey->getId()]);
            } catch (\Throwable $e) {
                $conn->rollBack();
                $this->getErrorMessage('Beim Speichern ist ein Fehler aufgetreten. ' . $e->getMessage());
                return $this->redirectToRoute('survey_home');
            }
        }
        return $this->render('survey/index/new.html.twig', [
            'form' => $form->createView(),
            'isAdmin' => \in_array('ROLE_ADMIN', $this->getUser()->getRoles()),
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param Survey $survey
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws \Exception
     */
    public function edit(Survey $survey, Request $request, MenuItem $menu): Response
    {
        if ($survey->getSchool() !== $this->getUser()->getCurrentSchool() && ! $this->isGranted("ROLE_ADMIN")) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        $form = $this->createForm(SurveyType::class, $survey, ['isAdmin' => $this->isGranted("ROLE_ADMIN")]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($survey);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('survey_home');
        }

        $menu['survey']->addChild('Umfrage bearbeiten', [
            'route' => 'survey_edit',
            'routeParameters' => ['id' => $survey->getId()]
        ]);

        return $this->render('survey/index/edit.html.twig', [
            'form' => $form->createView(),
            'survey' => $survey,
            'isAdmin' => \in_array('ROLE_ADMIN', $this->getUser()->getRoles()),
        ]);
    }

    /**
     * @Route("/questions/{id}", name="questions")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param Survey $survey
     * @param Request $request
     * @param MenuItem $menu
     * @return Response|JsonResponse
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Throwable
     */
    public function questions(Survey $survey, Request $request, MenuItem $menu)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var SurveyQuestionRepository $sqr */
            $sqr = $this->getDoctrine()->getRepository(SurveyQuestion::class);
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case "up":
                        $sqr->up($request->get('question_id', null));
                        break;
                    case "down":
                        $sqr->down($request->get('question_id', null));
                        break;
                    case "delete_question":
                        $c = $em->find(SurveyQuestion::class, $request->get('question_id', null));
                        $em->remove($c);
                        $em->flush();
                        break;
                }
                $sqr->reorderAll($survey);
                $em->flush();
            }
            return new JsonResponse($sqr->find4Ajax(
                $survey,
                $request->query->get('sort', 'name'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        $menu['survey']->addChild($survey->getName() . ': Fragen', [
            'route' => 'survey_questions',
            'routeParameters' => ['id' => $survey->getId()]
        ]);
//dd($survey->getQuestions()->getValues());
        return $this->render('survey/index/questions.html.twig', [
            'survey' => $survey
        ]);
    }

    /**
     * @Route("/questions/add/{id}/{type}", name="questions_add")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param Survey $survey
     * @param string $type
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws \Exception
     */
    public function addQuestion(Survey $survey, string $type, Request $request, MenuItem $menu): Response
    {
        if (! \in_array($type, \array_keys(SurveyQuestion::TYPE_LABELS))) {
            throw new \Exception('Type (' . $type . ') not found!');
        }
        $menu['survey']->addChild($survey->getName() . ': Fragen', [
            'route' => 'survey_questions',
            'routeParameters' => ['id' => $survey->getId()]
        ])->addChild('Frage hinzufügen', [
            'route' => 'survey_questions_add',
            'routeParameters' => ['id' => $survey->getId(), 'type' => $type]
        ]);
        $question = new SurveyQuestion();
        $question->setType($type);
        $question->setSurvey($survey);
        $question->setOrder($survey->getQuestions()->count() + 1);
        $form = $this->createForm(SurveyQuestionType::class, $question, ['type' => $type]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('survey_questions', ['id' => $survey->getId()]);
        }

        return $this->render('survey/index/add_question.html.twig', [
            'question' => $question,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/questions/edit/{id}", name="questions_edit")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param SurveyQuestion $question
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws \Throwable
     */
    public function editQuestion(SurveyQuestion $question, Request $request, MenuItem $menu): Response
    {
        $menu['survey']->addChild($question->getSurvey()->getName() . ': Fragen', [
            'route' => 'survey_questions',
            'routeParameters' => ['id' => $question->getSurvey()->getId()]
        ])->addChild($question->getQuestion(), [
            'route' => 'survey_questions_edit',
            'routeParameters' => ['id' => $question->getId()]
        ]);

        $surveyState = $question->getSurvey()->getState();
        $form = $this->createForm(
            SurveyQuestionType::class,
            $question,
            [
                'type' => $question->getType(),
                'isNew' => false,
                'surveyState' => $surveyState
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($surveyState) {
                throw new \Exception('Die Umfrage wurde bereits Aktiviert und kann nicht mehr verändert werden!');
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('survey_questions', ['id' => $question->getSurvey()->getId()]);
        }

        return $this->render('survey/index/edit_question.html.twig', [
            'question' => $question,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/result/{id}", name="result")
     * @param Survey $survey
     * @param MenuItem $menu
     * @return Response
     * @throws \Exception
     */
    public function result(Survey $survey, MenuItem $menu): Response
    {
        if ($survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        $menu['survey']->addChild($survey->getName() . ' Ergebnis', [
            'route' => 'survey_result',
            'routeParameters' => ['id' => $survey->getId()]
        ]);

        $notAnswered = [];
        foreach ($survey->getQuestions() as $question) {
            $notAnswered[$question->getId()] = $question->getNotAnswered();
        }

        return $this->render('survey/index/result.html.twig', [
            'survey' => $survey,
            'school' => $this->getUser()->getCurrentSchool(),
            'not_answered' => $notAnswered,
        ]);
    }

    /**
     * @Route("/list", name="list")
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function list(Request $request): JsonResponse
    {
        /** @var SurveyRepository $sr */
        $sr = $this->getDoctrine()->getRepository(Survey::class);
        if ($request->isMethod(Request::METHOD_POST)) {
            $em = $this->getDoctrine()->getManager();
            switch ($request->get('action', null)) {
                case "delete_survey":
                    $survey = $em->getRepository(Survey::class)->find($request->get('survey_id', null));
                    if ($survey->getState() !== 0 && $survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
                        throw new \Exception('No rights for survey!');
                    }
                    $em->remove($survey);
                    $em->flush();
                    break;
            }
        }

        return new JsonResponse($sr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'createdAt'),
            $request->query->getBoolean('sortDesc', true),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1)
        ));
    }

    /**
     * @Route("/listSurveyTemplates", name="list_survey_templates")
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function listSurveyTemplates(Request $request): JsonResponse
    {
        /** @var SurveyRepository $sr */
        $sr = $this->getDoctrine()->getRepository(Survey::class);
        if ($request->isMethod(Request::METHOD_POST)) {
            $em = $this->getDoctrine()->getManager();
            switch ($request->get('action', null)) {
                case "delete_survey":
                    $survey = $em->getRepository(Survey::class)->find($request->get('survey_id', null));
                    if ($survey->getState() !== 0 && $survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
                        throw new \Exception('No rights for survey!');
                    }
                    $em->remove($survey);
                    $em->flush();
                    break;
            }
        }

        return new JsonResponse($sr->findSurveyTemplates4Ajax(
            $request->query->get('sort', 'createdAt'),
            $request->query->getBoolean('sortDesc', true),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1)
        ));
    }

    /**
     * @Route("/list-closed", name="list_closed")
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function listClosed(Request $request): JsonResponse
    {
        /** @var SurveyRepository $sr */
        $sr = $this->getDoctrine()->getRepository(Survey::class);
        return new JsonResponse($sr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'createdAt'),
            $request->query->getBoolean('sortDesc', true),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1),
            true
        ));
    }

    /**
     * @Route("/proposal/categories", name="proposal_categories")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function proposalCategories(Request $request): JsonResponse
    {
        /** @var CategoryRepository $sr */
        $cr = $this->getDoctrine()->getRepository(Category::class);
        return new JsonResponse($cr->find4Ajax(
            $request->query->getAlnum('sort', 'date'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1)
        ));
    }

    /**
     * @Route("/proposal/questions/{id}", name="proposal_questions")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param Category $category
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function proposalQuestions(Category $category, Request $request): JsonResponse
    {
        /** @var QuestionRepository $sr */
        $cr = $this->getDoctrine()->getRepository(Question::class);
        return new JsonResponse($cr->find4Ajax(
            $category,
            $request->query->getAlnum('sort', 'date'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1)
        ));
    }

    /**
     * @Route("/state/{state}/{id}", name="state")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param int $state
     * @param Survey $survey
     * @return Response
     * @throws \Exception
     */
    public function state(int $state, Survey $survey): Response
    {
        if ($survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        if ($survey->getState() === Survey::STATE_NOT_ACTIVATED && $state === Survey::STATE_ACTIVE) {
            $survey->setState(Survey::STATE_ACTIVE);
            $survey->setActivatedAt(new \DateTime());
        }
        if ($survey->getState() === Survey::STATE_ACTIVE && $state === Survey::STATE_CLOSED) {
            $survey->setState(Survey::STATE_CLOSED);
            $survey->setClosesAt(new \DateTime());
        }
        if ($survey->getState() === Survey::STATE_CLOSED && $state === Survey::STATE_ACTIVE) {
            $survey->setState(Survey::STATE_ACTIVE);
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($survey);
        $em->flush();

        return $this->redirectToRoute('survey_home');
    }

    /**
     * @Route("/voucher/{id}", name="voucher")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param Survey $survey
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function voucher(Survey $survey, Request $request, MenuItem $menu): Response
    {
        if ($survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        if ($request->isXmlHttpRequest()) {
            /** @var SurveyVoucherRepository $svr */
            $svr = $this->getDoctrine()->getRepository(SurveyVoucher::class);

            return new JsonResponse($svr->find4Ajax(
                $survey,
                $request->query->get('sort', 'name'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        $form = $this->createFormBuilder()->create('voucher', FormType::class)->getForm();
        $form->add('numberOfVoucher', IntegerType::class, [
            'required' => true,
            'attr' => ['class' => 'mt-2'],
            'constraints' => [new NotBlank()]
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();
            $conn->beginTransaction();
            try {
                $this->createVoucher($survey, $form->getData()['numberOfVoucher']);
                $conn->commit();
                $this->getSuccessMessage('Voucher erfolgreich erstellt!');
                return $this->redirectToRoute('survey_voucher', ['id' => $survey->getId()]);
            } catch (\Throwable $e) {
                $conn->rollBack();
                $this->getErrorMessage('Beim Speichern ist ein Fehler aufgetreten. ' . $e->getMessage());
                return $this->redirectToRoute('survey_voucher', ['id' => $survey->getId()]);
            }
        }

        $menu['survey']->addChild('Voucher', [
            'route' => 'survey_voucher',
            'routeParameters' => ['id' => $survey->getId()]
        ]);

        return $this->render("survey/index/voucher.html.twig", [
            'survey' => $survey,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/voucher-download/{id}", name="voucher_download")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param Survey $survey
     * @return StreamedResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function downloadVoucher(Survey $survey): StreamedResponse
    {
        if ($survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        $spreadsheet = new Spreadsheet();
        /** @var Worksheet $sheet */
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Voucher');
        $row = 1;
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('A' . $row, 'Voucher');
        $sheet->setCellValue('B' . $row, 'Erstellt');
        $sheet->setCellValue('C' . $row, 'Benutzt');
        $row++;
        foreach ($survey->getVouchers() as $i => $voucher) {
            $sheet->setCellValue('A' . ($i + $row), $voucher->getVoucher());
            $sheet->setCellValue('B' . ($i + $row), $voucher->getCreatedAt()->format('d.m.Y H:i'));
            $sheet->setCellValue('C' . ($i + $row), $voucher->isInUse() ? 'Ja' : 'Nein');
        }
        foreach (\range('A', 'C') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(
            static function () use ($writer): void {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Voucher_' . \date('Y-m-d-H-i') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    /**
     * @param Survey $survey
     * @param int $number
     */
    protected function createVoucher(Survey $survey, int $number): void
    {
        $em = $this->getDoctrine()->getManager();
        for ($i = 0; $i < $number; $i++) {
            $_str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $code = \substr(\substr(\str_shuffle($_str), 0, 8), 0, 4);
            $code .= "-" . \substr(\substr(\str_shuffle($_str), 0, 4), 0, 4);
            $surveyVoucher = new SurveyVoucher();
            $surveyVoucher->setSurvey($survey);
            $surveyVoucher->setVoucher($code);
            $surveyVoucher->setCreatedBy($this->getUser());
            $em->persist($surveyVoucher);
            $em->flush($surveyVoucher);
        }
    }

    /**
     * @Route("/copy/{surveyId}", name="copy")
     * @param int $surveyId
     * @return Response
     * @throws InvalidArgumentException
     */
    public function copy(int $surveyId): Response
    {
        $oldSurvey = $this->getDoctrine()->getRepository(Survey::class)->findOneBy(['id' => $surveyId]);
        if (! $oldSurvey instanceof Survey) {
            throw new InvalidArgumentException('Die Umfrage konnte nicht gefunden werden!');
        }

        /** @var Survey $newSurvey */
        $newSurvey = clone $oldSurvey;
        $date = new \DateTime();
        $newSurvey->setName($date->format('d.m.Y H:i:s'))
            ->setCreatedBy($this->getUser())
            ->setSurveyTemplate(false)
            ->setSchool($this->getUser()->getCurrentSchool());

        $countVouchers = \count($this->getDoctrine()->getRepository(SurveyVoucher::class)->findBy(['survey' => $oldSurvey]));
        $this->em->persist($newSurvey);

        $this->createVoucher($newSurvey, $countVouchers);

        $this->em->persist($newSurvey);
        $this->em->flush();

        return $this->redirectToRoute('survey_home');
    }

    /**
     * @Route("/ajax", name="ajax")
     * @param Request $request
     * @return string
     */
    public function ajax(Request $request): string
    {
        $questionPosition = 0;
        $em = $this->getDoctrine()->getManager();


        $data = \json_decode($request->getContent(), true);
        foreach ($data['selectedQuestions'] as $selectedQuestion) {
            $questionPosition++;
            $questionFromPool = $this->getDoctrine()
                ->getRepository(Question::class)
                ->findOneBy(['id' => $selectedQuestion]);

            $newQuestion = new SurveyQuestion();
            $newQuestion->setQuestion($questionFromPool->getQuestion());
            $newQuestion->setType(SurveyQuestion::TYPE_HAPPY_UNHAPPY);
            $newQuestion->setSustainable($questionFromPool->isSustainable());

            $survey = $em->getRepository(Survey::class)->findOneBy(['id' => $data['surveyId']]);
            $newQuestion->setSurvey($survey);

            $order = $survey->getQuestions()->count() + $questionPosition;
            $newQuestion->setOrder($order);

            $em->persist($newQuestion);
            $em->flush();
        }

        return new JsonResponse($survey->getQuestions()->count());
    }

    /**
     * @Route("/export/{survey}", name="export")
     * @param Survey $survey
     * @return StreamedResponse
     * @throws Exception
     */
    public function export(Survey $survey): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getNumberFormat()->setFormatCode('#');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Zufriedenheitsumfrage');
        $sheet->setCellValue('A1', $survey->getSchool() . ' vom ' . \date('d.m.Y'));

        $row = 3;
        $sheet->setCellValue('A' . $row, 'Frage');
        $sheet->setCellValue('B' . $row, 'Nachhaltigkeit');
        $sheet->setCellValue('C' . $row, 'Antworten');
        $row++;
        foreach ($survey->getQuestions() as $i => $question) {
            $sheet->setCellValue('A' . ($i + $row), "" . $question->getQuestion());
            $sheet->setCellValue('B' . ($i + $row), $question->isSustainable() ? 'Ja' : '');
            if ($question->getType() === SurveyQuestion::TYPE_HAPPY_UNHAPPY) {
                $sheet->setCellValue('C' . ($i + $row), 'Ja');
                $sheet->setCellValue('D' . ($i + $row), 'Nein');
                $sheet->setCellValue('E' . ($i + $row), 'Nicht beantwortet');
                $row++;
                $sheet->setCellValue('C' . ($i + $row), $question->getAnswers(true)->count());
                $sheet->setCellValue('D' . ($i + $row), $question->getAnswers(false)->count());
                $sheet->setCellValue('E' . ($i + $row), $question->getNotAnswered());
            } else {
                foreach ($question->getChoices() as $j => $choices) {
                    $sheet->setCellValue($this->intToChar($j + 2) . ($i + $row), $choices->getChoice());
                    $sheet->setCellValue($this->intToChar($j + 2) . ($i + $row + 1), $choices->getAnswers()->count());
                }
                $j++;
                $sheet->setCellValue($this->intToChar($j + 2) . ($i + $row), 'Nicht beantwortet');
                $sheet->setCellValue($this->intToChar($j + 2) . ($i + $row + 1), $question->getNotAnswered());
                $row++;
            }
            $row++;
        }

        foreach (\range('A', 'Z') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(
            static function () use ($writer): void {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Zufriedenheitsumfrage ' . $survey->getSchool() . ' vom ' . \date('d.m.Y') . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    /**
     * @param int $i
     * @return string
     */
    public function intToChar(int $i): string
    {
        for ($r = ""; $i >= 0; $i = \intval($i / 26) - 1) {
            $r = \chr($i % 26 + 0x41) . $r;
        }
        return $r;
    }

    /**
     * @param string $char
     * @return int
     */
    public function charToInt(string $char): int
    {
        $res = 0;
        if (\strlen($char) > 1) {
            if ($char[0] === 'I') {
                $res = 234;
            } elseif ($char[0] === 'H') {
                $res = 208;
            } elseif ($char[0] === 'G') {
                $res = 182;
            } elseif ($char[0] === 'F') {
                $res = 156;
            } elseif ($char[0] === 'E') {
                $res = 130;
            } elseif ($char[0] === 'D') {
                $res = 104;
            } elseif ($char[0] === 'C') {
                $res = 78;
            } elseif ($char[0] === 'B') {
                $res = 52;
            } elseif ($char[0] === 'A') {
                $res = 26;
            }
        }

        return $res + \ord($char);
    }
}
