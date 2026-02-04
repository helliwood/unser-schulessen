<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\QualityCheck\Ideabox;
use App\Entity\QualityCheck\Question;
use App\Form\IdeaboxType;
use App\Repository\IdeaboxRepository;
use Exception;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * @Route("/admin/questionnaire/category/questions/ideabox", name="admin_questionnaire_category_questions_ideabox_")
 * @IsGranted("ROLE_ADMIN")
 */
class IdeaboxController extends AbstractController
{
    /**
     * @Route("/{id}", name="home")
     * @param Question $question
     * @param Request  $request
     * @param MenuItem $menu
     * @return RedirectResponse|Response
     * @throws Exception
     * @throws Throwable
     */
    public function index(Question $question, Request $request, MenuItem $menu)
    {
        if ($request->isXmlHttpRequest()) {

            /** @var IdeaboxRepository $ir */
            $ir = $this->getDoctrine()->getRepository(Ideabox::class);
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case "up":
                        $ir->up($request->get('ideabox_id', null));
                        break;
                    case "down":
                        $ir->down($request->get('ideabox_id', null));
                        break;
                    case "delete_idea":
                        $c = $em->find(Ideabox::class, $request->get('ideabox_id', null));
                        $em->remove($c);
                        $em->flush();
                        break;
                }
                $question->reorderIdeaboxes();
                $em->flush();
            }

            return new JsonResponse($ir->find4Ajax(
                $question,
                $request->query->getAlnum('sort', 'date'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }
        //for breadcrumb
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
        ])->addChild('Ideenbox', [
            'route' => 'admin_questionnaire_category_questions_ideabox_home',
            'routeParameters' => ['id' => $question->getId()]
        ]);
        return $this->render('admin/ideabox/index.html.twig', [
            'question' => $question
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit", requirements={"id": "\d+"})
     * @param Ideabox  $ideabox
     * @param Request  $request
     * @param MenuItem $menu
     * @return RedirectResponse|Response
     * */
    public function edit(Ideabox $ideabox, Request $request, MenuItem $menu)
    {
        $form = $this->createForm(IdeaboxType::class, $ideabox, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($ideabox);
            $em->flush();
            $this->getSuccessMessage();

            return $this->redirectToRoute(
                'admin_questionnaire_category_questions_ideabox_home',
                ['id' => $ideabox->getQuestion()->getId()]
            );
        }

        $menu = $menu['admin']['questionnaire']->addChild($ideabox->getQuestion()->getCategory()->getQuestionnaire()->getName(), [
            'route' => 'admin_questionnaire_show',
            'routeParameters' => ['id' => $ideabox->getQuestion()->getCategory()->getQuestionnaire()->getId()],
        ]);

        if ($ideabox->getQuestion()->getCategory()->getParent()) {
            $menu = $menu->addChild($ideabox->getQuestion()->getCategory()->getParent()->getName(), [
                'route' => 'admin_questionnaire_category_questions_home',
                'routeParameters' => ['id' => $ideabox->getQuestion()->getCategory()->getParent()->getId()]
            ]);
        }

        $menu->addChild($ideabox->getQuestion()->getCategory()->getName(), [
            'route' => 'admin_questionnaire_category_questions_home',
            'routeParameters' => ['id' => $ideabox->getQuestion()->getCategory()->getId()]
        ])->addChild('Ideenbox', [
            'route' => 'admin_questionnaire_category_questions_ideabox_home',
            'routeParameters' => ['id' => $ideabox->getQuestion()->getId()]
        ])->addChild('Idee bearbeiten', [
            'route' => 'admin_questionnaire_category_questions_ideabox_edit',
            'routeParameters' => ['id' => $ideabox->getId()]
        ]);

        return $this->render('admin/ideabox/edit.html.twig', [
            'form' => $form->createView(),
            'ideabox' => $ideabox
        ]);
    }

    /**
     * @Route("/new/{id}", name="new")
     * @param Request  $request
     * @param Question $question
     * @param MenuItem $menu
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function new(Request $request, Question $question, MenuItem $menu)
    {
        $ideabox = new Ideabox();
        $ideabox->setQuestion($question);
        $ideabox->setOrder($question->getIdeaboxes()->count() + 1);

        $form = $this->createForm(IdeaboxType::class, $ideabox, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($ideabox);
            $em->flush($ideabox);
            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_questionnaire_category_questions_ideabox_home', [
                'id' => $question->getId()
            ]);
        }

        $menu = $menu['admin']['questionnaire']->addChild($ideabox->getQuestion()->getCategory()->getQuestionnaire()->getName(), [
            'route' => 'admin_questionnaire_show',
            'routeParameters' => ['id' => $ideabox->getQuestion()->getCategory()->getQuestionnaire()->getId()],
        ]);

        if ($ideabox->getQuestion()->getCategory()->getParent()) {
            $menu = $menu->addChild($ideabox->getQuestion()->getCategory()->getParent()->getName(), [
                'route' => 'admin_questionnaire_category_questions_home',
                'routeParameters' => ['id' => $ideabox->getQuestion()->getCategory()->getParent()->getId()]
            ]);
        }

        $menu->addChild($ideabox->getQuestion()->getCategory()->getName() . ' Fragen', [
            'route' => 'admin_questionnaire_category_questions_home',
            'routeParameters' => ['id' => $ideabox->getQuestion()->getCategory()->getId()]
        ])->addChild('Ideenbox', [
            'route' => 'admin_questionnaire_category_questions_ideabox_home',
            'routeParameters' => ['id' => $question->getId()]
        ])->addChild('Neue Idee', [
            'route' => 'admin_questionnaire_category_questions_ideabox_new',
            'routeParameters' => ['id' => $question->getId()]
        ]);

        return $this->render('admin/ideabox/new.html.twig', [
            'form' => $form->createView(),
            'question' => $question
        ]);
    }
}
