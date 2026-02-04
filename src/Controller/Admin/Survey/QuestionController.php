<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 08.08.19
 * Time: 15:46
 */

namespace App\Controller\Admin\Survey;

use App\Controller\AbstractController;
use App\Entity\Survey\Category;
use App\Entity\Survey\Question;
use App\Form\Survey\QuestionType;
use App\Repository\Survey\QuestionRepository;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/survey/questions", name="admin_survey_questions_")
 * @IsGranted("ROLE_ADMIN")
 */
class QuestionController extends AbstractController
{
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
                $em = $this->getDoctrine()->getManager();
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
        $menu['admin']['survey']->addChild($category->getName() . ' - Fragen', [
            'route' => 'admin_survey_questions_home',
            'routeParameters' => ['id' => $category->getId()],

        ]);

        return $this->render('admin/survey/question/index.html.twig', [
            'category' => $category
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
        $form = $this->createForm(QuestionType::class, $question, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush();
            $this->getSuccessMessage();

            return $this->redirectToRoute(
                'admin_survey_questions_home',
                ['id' => $question->getCategory()->getId()]
            );
        }

        $menu['admin']['survey']->addChild($question->getCategory()->getName() . ' - Fragen', [
            'route' => 'admin_survey_questions_home',
            'routeParameters' => ['id' => $question->getCategory()->getId()],

        ])->addChild('Frage bearbeiten', [
            'route' => 'admin_survey_questions_edit',
            'routeParameters' => ['id' => $question->getId()]
        ]);

        return $this->render('admin/survey/question/edit.html.twig', [
            'form' => $form->createView(),
            'question' => $question
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
            $em = $this->getDoctrine()->getManager();
            $em->persist($question);
            $em->flush($question);
            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_survey_questions_home', [
                'id' => $category->getId()
            ]);
        }


        $menu['admin']['survey']->addChild($question->getCategory()->getName() . ' - Fragen', [
            'route' => 'admin_survey_questions_home',
            'routeParameters' => ['id' => $question->getCategory()->getId()],

        ])->addChild('Neue Frage', [
            'route' => 'admin_survey_questions_new',
            'routeParameters' => ['id' => $category->getId()]
        ]);

        return $this->render('admin/survey/question/new.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
