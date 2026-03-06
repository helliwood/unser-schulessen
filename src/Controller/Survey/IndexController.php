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

#[Route(path: '/survey', name: 'survey_')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_USER')]
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

    private function assertCanAccessSurvey(Survey $survey): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->getUser();
        if ($survey->getSchoolAuthority() !== null) {
            if (! $this->isGranted('ROLE_SCHOOL_AUTHORITY')) {
                throw new AccessDeniedException('Schule nicht gestattet.');
            }
            if (! $user || $survey->getSchoolAuthority() !== $user->getSchoolAuthority()) {
                throw new AccessDeniedException('Schule nicht gestattet.');
            }
            return;
        }

        if (! $survey->getSchool() || $survey->getSchool() !== $user->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }
    }

    private function assertSchoolSurveyAccess(Survey $survey): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->getUser();
        if (! $survey->getSchool() || $survey->getSchool() !== $user->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }
    }

    private function getSurveyMenuKey(): string
    {
        return $this->isGranted('ROLE_SCHOOL_AUTHORITY') ? 'school_authority_surveys' : 'survey';
    }

    /**
     * @return Response
     * @throws \Exception
     */
    #[Route(path: '/', name: 'home')]
    public function index(): Response
    {
        return $this->render('survey/index/index.html.twig', [
            'school' => $this->getUser()->getCurrentSchool()
        ]);
    }

    /**
     * @param MenuItem $menu
     * @return Response
     */
    #[Route(path: '/papierkorb', name: 'trash')]
    public function trash(MenuItem $menu): Response
    {
        $menu[$this->getSurveyMenuKey()]->addChild('Papierkorb', [
            'route' => 'survey_trash',
        ]);

        return $this->render('survey/index/trash.html.twig', [
            'school' => $this->getUser()->getCurrentSchool(),
        ]);
    }

    /**
     * @param Request $request
     * @param string|null $uuid
     * @return Response
     * @throws ConnectionException
     */
    #[Route(path: '/new/{uuid}', name: 'new', defaults: ['uuid' => null])]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE') or is_granted('ROLE_ADMIN')"))]
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
                $survey->setSchoolAuthority(null);
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
     * @param Survey $survey
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws \Exception
     */
    #[Route(path: '/edit/{id}', name: 'edit')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE') or is_granted('ROLE_SCHOOL_AUTHORITY') or is_granted('ROLE_ADMIN')"))]
    public function edit(Survey $survey, Request $request, MenuItem $menu): Response
    {
        $this->assertCanAccessSurvey($survey);

        $form = $this->createForm(SurveyType::class, $survey, ['isAdmin' => $this->isGranted("ROLE_ADMIN")]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($survey);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('survey_home');
        }

        $menu[$this->getSurveyMenuKey()]->addChild('Umfrage bearbeiten', [
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
     * @param Survey $survey
     * @param Request $request
     * @param MenuItem $menu
     * @return Response|JsonResponse
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Throwable
     */
    #[Route(path: '/questions/{id}', name: 'questions')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE') or is_granted('ROLE_SCHOOL_AUTHORITY') or is_granted('ROLE_ADMIN')"))]
    public function questions(Survey $survey, Request $request, MenuItem $menu)
    {
        $this->assertCanAccessSurvey($survey);
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

        $menu[$this->getSurveyMenuKey()]->addChild($survey->getName() . ': Fragen', [
            'route' => 'survey_questions',
            'routeParameters' => ['id' => $survey->getId()]
        ]);
//dd($survey->getQuestions()->getValues());
        return $this->render('survey/index/questions.html.twig', [
            'survey' => $survey
        ]);
    }

    /**
     * @param Survey $survey
     * @param string $type
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws \Exception
     */
    #[Route(path: '/questions/add/{id}/{type}', name: 'questions_add')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE') or is_granted('ROLE_SCHOOL_AUTHORITY') or is_granted('ROLE_ADMIN')"))]
    public function addQuestion(Survey $survey, string $type, Request $request, MenuItem $menu): Response
    {
        $this->assertCanAccessSurvey($survey);
        if (! \in_array($type, \array_keys(SurveyQuestion::TYPE_LABELS))) {
            throw new \Exception('Type (' . $type . ') not found!');
        }
        $menu[$this->getSurveyMenuKey()]->addChild($survey->getName() . ': Fragen', [
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
     * @param SurveyQuestion $question
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws \Throwable
     */
    #[Route(path: '/questions/edit/{id}', name: 'questions_edit')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE') or is_granted('ROLE_SCHOOL_AUTHORITY') or is_granted('ROLE_ADMIN')"))]
    public function editQuestion(SurveyQuestion $question, Request $request, MenuItem $menu): Response
    {
        $this->assertCanAccessSurvey($question->getSurvey());
        $menu[$this->getSurveyMenuKey()]->addChild($question->getSurvey()->getName() . ': Fragen', [
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
     * @param Survey $survey
     * @param MenuItem $menu
     * @return Response
     * @throws \Exception
     */
    #[Route(path: '/result/{id}', name: 'result')]
    public function result(Survey $survey, MenuItem $menu): Response
    {
        $this->assertCanAccessSurvey($survey);

        $menu[$this->getSurveyMenuKey()]->addChild($survey->getName() . ' Ergebnis', [
            'route' => 'survey_result',
            'routeParameters' => ['id' => $survey->getId()]
        ]);

        $notAnswered = [];
        foreach ($survey->getQuestions() as $question) {
            $notAnswered[$question->getId()] = $question->getNotAnswered();
        }

        $schoolLabel = $survey->getSchool()
            ? $survey->getSchool()->getName()
            : ($survey->getSchoolAuthority() ? $survey->getSchoolAuthority()->getName() : '');

        return $this->render('survey/index/result.html.twig', [
            'survey' => $survey,
            'school' => $schoolLabel,
            'not_answered' => $notAnswered,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    #[Route(path: '/list', name: 'list')]
    public function list(Request $request): JsonResponse
    {
        /** @var SurveyRepository $sr */
        $sr = $this->getDoctrine()->getRepository(Survey::class);
        if ($request->isMethod(Request::METHOD_POST)) {
            $em = $this->getDoctrine()->getManager();
            switch ($request->get('action', null)) {
                case "delete_survey":
                    $survey = $em->getRepository(Survey::class)->find($request->get('survey_id', null));
                    if (! $survey || ! $survey->getSchool() || $survey->isSchoolAuthoritySurvey()) {
                        throw new \Exception('No rights for survey!');
                    }
                    if ($survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
                        throw new \Exception('No rights for survey!');
                    }
                    if ($survey->getState() !== Survey::STATE_CLOSED && $survey->getState() !== Survey::STATE_NOT_ACTIVATED) {
                        throw new \Exception('Nur beendete oder nicht gestartete Umfragen können in den Papierkorb verschoben werden.');
                    }
                    $survey->setDeleted(true);
                    $em->persist($survey);
                    $em->flush();
                    break;
                case "restore_survey":
                    $survey = $em->getRepository(Survey::class)->find($request->get('survey_id', null));
                    if (! $survey || ! $survey->getSchool() || $survey->isSchoolAuthoritySurvey()) {
                        throw new \Exception('No rights for survey!');
                    }
                    if ($survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
                        throw new \Exception('No rights for survey!');
                    }
                    $survey->setDeleted(false);
                    $em->persist($survey);
                    $em->flush();
                    break;
            }
        }

        $search = \trim((string) $request->query->get('search', ''));
        if ($search !== '') {
            $school = $this->getUser()->getCurrentSchool();
            $qb = $this->getDoctrine()->getRepository(Survey::class)
                ->createQueryBuilder('s')
                ->where('s.school = :school')
                ->andWhere('s.surveyTemplate = false')
                ->andWhere('s.deleted = false')
                ->andWhere('s.name LIKE :search')
                ->setParameter('school', $school)
                ->setParameter('search', '%' . $search . '%');

            $totalRows = (int) $qb->select('COUNT(s.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $items = $qb->select('s')
                ->orderBy('s.createdAt', 'DESC')
                ->setFirstResult(($request->query->getInt('page', 1) - 1) * $request->query->getInt('size', 1))
                ->setMaxResults($request->query->getInt('size', 1))
                ->getQuery()
                ->getResult();

            return new JsonResponse([
                'totalRows' => $totalRows,
                'items' => $items,
            ]);
        }

        return new JsonResponse($sr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'createdAt'),
            $request->query->getBoolean('sortDesc', true),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1),
            false,
            false
        ));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    #[Route(path: '/listSurveyTemplates', name: 'list_survey_templates')]
    public function listSurveyTemplates(Request $request): JsonResponse
    {
        /** @var SurveyRepository $sr */
        $sr = $this->getDoctrine()->getRepository(Survey::class);
        if ($request->isMethod(Request::METHOD_POST)) {
            $em = $this->getDoctrine()->getManager();
            switch ($request->get('action', null)) {
                case "delete_survey":
                    $survey = $em->getRepository(Survey::class)->find($request->get('survey_id', null));
                    if (! $survey->getSchool() || $survey->isSchoolAuthoritySurvey()) {
                        throw new \Exception('No rights for survey!');
                    }
                    if ($survey->getState() !== 0 && $survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
                        throw new \Exception('No rights for survey!');
                    }
                    $em->remove($survey);
                    $em->flush();
                    break;
            }
        }

        $sort = $request->query->get('sort', 'createdAt');
        $sortDesc = $request->query->getBoolean('sortDesc', true);
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 1);

        $templates = $sr->createQueryBuilder('s')
            ->where('s.surveyTemplate = true')
            ->andWhere('s.schoolAuthority IS NULL')
            ->andWhere('s.deleted = false')
            ->getQuery()
            ->getResult();

        $authorityTemplates = [];
        $currentSchool = $this->getUser()->getCurrentSchool();
        if ($currentSchool && $currentSchool->getSchoolAuthority()) {
            $authorityTemplates = $sr->createQueryBuilder('s')
                ->join('s.schoolParticipations', 'sp')
                ->where('s.surveyTemplate = true')
                ->andWhere('s.schoolAuthority = :schoolAuthority')
                ->andWhere('s.state = :state')
                ->andWhere('sp.school = :school')
                ->andWhere('s.deleted = false')
                ->setParameter('schoolAuthority', $currentSchool->getSchoolAuthority())
                ->setParameter('state', Survey::STATE_ACTIVE)
                ->setParameter('school', $currentSchool)
                ->getQuery()
                ->getResult();
        }

        $items = [];
        foreach (\array_merge($authorityTemplates, $templates) as $survey) {
            if (! $survey instanceof Survey) {
                continue;
            }
            // Defensive filter: only real templates are allowed in this list.
            if (! $survey->getSurveyTemplate()) {
                continue;
            }
            $createdAt = $survey->getCreatedAt();
            $closesAt = $survey->getClosesAt();
            $items[] = [
                'id' => $survey->getId(),
                'uuid' => $survey->getUuid(),
                'name' => $survey->getName(),
                'type' => $survey->getType(),
                'typeLabel' => Survey::TYPE_LABELS[$survey->getType()] ?? null,
                'state' => $survey->getState(),
                'createdAt' => $createdAt ? $createdAt->getTimestamp() : 0,
                'closesAt' => $closesAt ? $closesAt->format('d.m.Y') : '-',
                'isAuthorityTemplate' => $survey->isSchoolAuthorityTemplate(),
            ];
        }

        $sortValues = ['name', 'createdAt', 'type', 'state'];
        if (! \in_array($sort, $sortValues, true)) {
            $sort = 'createdAt';
        }

        \usort($items, static function (array $a, array $b) use ($sort, $sortDesc): int {
            $left = $a[$sort] ?? null;
            $right = $b[$sort] ?? null;
            if (\is_string($left)) {
                $left = \mb_strtolower($left);
            }
            if (\is_string($right)) {
                $right = \mb_strtolower($right);
            }
            $result = $left <=> $right;
            return $sortDesc ? -$result : $result;
        });

        $totalRows = \count($items);
        $items = \array_slice($items, ($page - 1) * $size, $size);

        return new JsonResponse([
            'totalRows' => $totalRows,
            'items' => \array_values($items),
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    #[Route(path: '/list-closed', name: 'list_closed')]
    public function listClosed(Request $request): JsonResponse
    {
        /** @var SurveyRepository $sr */
        $sr = $this->getDoctrine()->getRepository(Survey::class);
        if ($request->isMethod(Request::METHOD_POST) && $request->get('action') === 'delete_survey') {
            $em = $this->getDoctrine()->getManager();
            $survey = $em->getRepository(Survey::class)->find($request->get('survey_id', null));
            if (! $survey || ! $survey->getSchool() || $survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
                throw new \Exception('No rights for survey!');
            }
            if ($survey->getState() !== Survey::STATE_CLOSED) {
                throw new \Exception('Nur beendete Umfragen können in den Papierkorb verschoben werden.');
            }
            $survey->setDeleted(true);
            $em->persist($survey);
            $em->flush();
        }
        return new JsonResponse($sr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'createdAt'),
            $request->query->getBoolean('sortDesc', true),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1),
            true,
            false
        ));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    #[Route(path: '/list-deleted', name: 'list_deleted')]
    public function listDeleted(Request $request): JsonResponse
    {
        /** @var SurveyRepository $sr */
        $sr = $this->getDoctrine()->getRepository(Survey::class);
        if ($request->isMethod(Request::METHOD_POST) && $request->get('action') === 'restore_survey') {
            $em = $this->getDoctrine()->getManager();
            $survey = $em->getRepository(Survey::class)->find($request->get('survey_id', null));
            if (! $survey || ! $survey->getSchool() || $survey->getSchool() !== $this->getUser()->getCurrentSchool()) {
                throw new \Exception('No rights for survey!');
            }
            $survey->setDeleted(false);
            $em->persist($survey);
            $em->flush();
        }

        return new JsonResponse($sr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'createdAt'),
            $request->query->getBoolean('sortDesc', true),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1),
            false,
            true
        ));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    #[Route(path: '/proposal/categories', name: 'proposal_categories')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE') or is_granted('ROLE_SCHOOL_AUTHORITY') or is_granted('ROLE_ADMIN')"))]
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
     * @param Category $category
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    #[Route(path: '/proposal/questions/{id}', name: 'proposal_questions')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE') or is_granted('ROLE_SCHOOL_AUTHORITY') or is_granted('ROLE_ADMIN')"))]
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
     * @param int $state
     * @param Survey $survey
     * @return Response
     * @throws \Exception
     */
    #[Route(path: '/state/{state}/{id}', name: 'state')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')"))]
    public function state(int $state, Survey $survey): Response
    {
        $this->assertCanAccessSurvey($survey);

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
     * @param Survey $survey
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\DBAL\ConnectionException
     */
    #[Route(path: '/voucher/{id}', name: 'voucher')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')"))]
    public function voucher(Survey $survey, Request $request, MenuItem $menu): Response
    {
        $this->assertSchoolSurveyAccess($survey);

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
     * @param Survey $survey
     * @return StreamedResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route(path: '/voucher-download/{id}', name: 'voucher_download')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')"))]
    public function downloadVoucher(Survey $survey): StreamedResponse
    {
        $this->assertSchoolSurveyAccess($survey);

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
     * @param int $surveyId
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route(path: '/copy/{surveyId}', name: 'copy')]
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
            ->setSchoolAuthority(null)
            ->setSchool($this->getUser()->getCurrentSchool());

        $countVouchers = \count($this->getDoctrine()->getRepository(SurveyVoucher::class)->findBy(['survey' => $oldSurvey]));
        $this->em->persist($newSurvey);

        $this->createVoucher($newSurvey, $countVouchers);

        $this->em->persist($newSurvey);
        $this->em->flush();

        return $this->redirectToRoute('survey_home');
    }

    /**
     * @param Request $request
     * @return string
     */
    #[Route(path: '/ajax', name: 'ajax')]
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
            if (! $survey instanceof Survey) {
                throw new InvalidArgumentException('Die Umfrage konnte nicht gefunden werden!');
            }
            $this->assertCanAccessSurvey($survey);
            $newQuestion->setSurvey($survey);

            $order = $survey->getQuestions()->count() + $questionPosition;
            $newQuestion->setOrder($order);

            $em->persist($newQuestion);
            $em->flush();
        }

        return new JsonResponse($survey->getQuestions()->count());
    }

    /**
     * @param Survey $survey
     * @return StreamedResponse
     * @throws Exception
     */
    #[Route(path: '/export/{survey}', name: 'export')]
    public function export(Survey $survey): StreamedResponse
    {
        $this->assertSchoolSurveyAccess($survey);

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
            } elseif ($question->getType() === SurveyQuestion::TYPE_TEXT) {
                $sheet->setCellValue('C' . ($i + $row), 'Freitext Antworten');
                $row++;
                foreach ($question->getAnswers() as $j => $answer) {
                    $sheet->setCellValue('C' . ($i + $row + $j), $answer->getTextAnswer());
                }
                $row += \count($question->getAnswers()) - 1;
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
