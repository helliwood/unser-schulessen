<?php
/**
 * Created by PhpStorm.
 * User: victoria
 * Date: 24.06.19
 * Time: 11:49
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Question;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use App\Service\QualityCheckService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/questionnaire/category/questions", name="admin_questionnaire_category_questions_")
 * @IsGranted("ROLE_ADMIN")
 */
class QuestionController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    
    /**
     * @var QualityCheckService
     */
    private $qualityCheckService;

    public function __construct(EntityManagerInterface $entityManager, QualityCheckService $qualityCheckService)
    {
        $this->entityManager = $entityManager;
        $this->qualityCheckService = $qualityCheckService;
    }
    /**
     * @Route("/{id}", name="home")
     * @param Category $category
     * @param Request  $request
     * @param MenuItem $menu
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     * @throws \Throwable
     */
    public function index(Category $category, Request $request, MenuItem $menu)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var QuestionRepository $qr */
            $qr = $this->getDoctrine()->getRepository(Question::class);
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->entityManager;
                switch ($request->get('action', null)) {
                    case "up":
                        $qr->up($request->get('question_id', null));
                        break;
                    case "down":
                        $qr->down($request->get('question_id', null));
                        break;
                    case "delete_question":
                        $c = $em->find(Question::class, $request->get('question_id', null));
                        $em->remove($c);
                        $em->flush();
                        break;
                }
                $category->reorderQuestions();
                $em->flush();
            }

            return new JsonResponse($qr->find4Ajax(
                $category,
                $request->query->getAlnum('sort', 'date'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }
        // for breadcrumb
        $menu = $menu['admin']['questionnaire']->addChild($category->getQuestionnaire()->getName(), [
            'route' => 'admin_questionnaire_show',
            'routeParameters' => ['id' => $category->getQuestionnaire()->getId()],
        ]);

        if ($category->getParent()) {
            $menu = $menu->addChild($category->getParent()->getName(), [
                'route' => 'admin_questionnaire_category_questions_home',
                'routeParameters' => ['id' => $category->getParent()->getId()]
            ]);
        }

        $menu->addChild($category->getName(), [
            'route' => 'admin_questionnaire_category_questions_home',
            'routeParameters' => ['id' => $category->getId()]
        ]);

        return $this->render('admin/question/index.html.twig', [
            'category' => $category,
            'flag_definitions' => $this->qualityCheckService->getFlagDefinitions(),
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit", requirements={"id": "\d+"})
     * @param Question $question
     * @param Request  $request
     * @param MenuItem $menu
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * */
    public function edit(Question $question, Request $request, MenuItem $menu)
    {
        $questionnaireIsActivated = $question->getCategory()->getQuestionnaire()->getState() !== 0;
        $form = $this->createForm(QuestionType::class, $question, ['questionnaireIsActivated' => $questionnaireIsActivated]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->entityManager;

            if ($questionnaireIsActivated) {
                $question->setType($question->getType());
                $question->setFormula($question->getFormula());
            }
            if ($question->getType() === Question::TYPE_NOT_NEEDED && $question->getFormula()) {
                $em->remove($question->getFormula());
                $question->setFormula(null);
            }
            $em->persist($question);
            $em->flush();
            $this->getSuccessMessage();

            return $this->redirectToRoute(
                'admin_questionnaire_category_questions_home',
                ['id' => $question->getCategory()->getId()]
            );
        }

        $menu = $menu['admin']['questionnaire']->addChild($question->getCategory()->getQuestionnaire()->getName(), [
            'route' => 'admin_questionnaire_show',
            'routeParameters' => ['id' => $question->getCategory()->getQuestionnaire()->getId()],
        ]);

        if ($question->getCategory()->getParent()) {
            $menu = $menu->addChild($question->getCategory()->getParent()->getName(), [
                'route' => 'admin_questionnaire_category_questions_home',
                'routeParameters' => ['id' => $question->getCategory()->getParent()->getId()]
            ]);
        }

        $menu->addChild($question->getCategory()->getName(), [
            'route' => 'admin_questionnaire_category_questions_home',
            'routeParameters' => ['id' => $question->getCategory()->getId()]
        ])->addChild('Frage bearbeiten', [
            'route' => 'admin_questionnaire_category_questions_edit',
            'routeParameters' => ['id' => $question->getId()]
        ]);

        return $this->render('admin/question/edit.html.twig', [
            'form' => $form->createView(),
            'question' => $question,
            'flag_definitions' => $this->qualityCheckService->getFlagDefinitions()
        ]);
    }

    /**
     * @Route("/new/{id}", name="new")
     * @param Request  $request
     * @param Category $category
     * @param MenuItem $menu
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function new(Request $request, Category $category, MenuItem $menu)
    {
        $question = new Question();
        $question->setCategory($category);
        $question->setOrder($category->getQuestions()->count() + 1);

        $form = $this->createForm(QuestionType::class, $question, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->entityManager;
            if ($question->getType() === Question::TYPE_NOT_NEEDED && $question->getFormula()) {
                $question->setFormula(null);
            }

            $questionAlreadyExists = $em->getRepository(Question::class)->findOneBy(['question' => $question->getQuestion(), 'category' => $question->getCategory()]);
            if (! \is_null($questionAlreadyExists)) {
                $form->get('question')->addError(new FormError('Diese Frage wurde exakt so bereits gestellt!'));
                return $this->render('admin/question/new.html.twig', [
                    'form' => $form->createView(),
                    'flag_definitions' => $this->qualityCheckService->getFlagDefinitions()
                ]);
            }
            $em->persist($question);
            $em->flush($question);
            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_questionnaire_category_questions_home', [
                'id' => $category->getId()
            ]);
        }

        $menu = $menu['admin']['questionnaire']->addChild($category->getQuestionnaire()->getName(), [
            'route' => 'admin_questionnaire_show',
            'routeParameters' => ['id' => $category->getQuestionnaire()->getId()],
        ]);

        if ($category->getParent()) {
            $menu = $menu->addChild($category->getParent()->getName(), [
                'route' => 'admin_questionnaire_category_questions_home',
                'routeParameters' => ['id' => $category->getParent()->getId()]
            ]);
        }

        $menu->addChild($category->getName(), [
            'route' => 'admin_questionnaire_category_questions_home',
            'routeParameters' => ['id' => $category->getId()]
        ])->addChild('Neue Frage', [
            'route' => 'admin_questionnaire_category_questions_new',
            'routeParameters' => ['id' => $category->getId()]
        ]);

        return $this->render('admin/question/new.html.twig', [
            'form' => $form->createView(),
            'flag_definitions' => $this->qualityCheckService->getFlagDefinitions()
        ]);
    }
}
