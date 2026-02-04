<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-08
 * Time: 15:14
 */

namespace App\Controller\Admin\Survey;

use App\Controller\AbstractController;
use App\Entity\Survey\Category;
use App\Form\Survey\CategoryType;
use App\Repository\Survey\CategoryRepository;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/survey", name="admin_survey_")
 * @IsGranted("ROLE_ADMIN")
 */
class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Throwable
     */
    public function index(Request $request): Response
    {
        /** @var CategoryRepository $cr */
        $cr = $this->getDoctrine()->getRepository(Category::class);
        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case "up":
                        $cr->up($request->get('category_id', null));
                        break;
                    case "down":
                        $cr->down($request->get('category_id', null));
                        break;
                    case "delete_category":
                        $c = $em->find(Category::class, $request->get('category_id', null));
                        $em->remove($c);
                        $em->flush();
                        break;
                }
                $cr->reorderAll();
                $em->flush();
            }

            return new JsonResponse($cr->find4Ajax(
                $request->query->getAlnum('sort', 'date'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        return $this->render('admin/survey/index/index.html.twig', [
            'total' => $cr->count([])
        ]);
    }

    /**
     * @Route("/new", name="new")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function new(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var CategoryRepository $cr */
        $cr = $this->getDoctrine()->getRepository(Category::class);
        $category = new Category();
        $category->setOrder($cr->count([]) + 1);

        $form = $this->createForm(CategoryType::class, $category, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_survey_home');
        }
        return $this->render('admin/survey/index/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @param Category $category
     * @param Request  $request
     * @param MenuItem $menu
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function edit(Category $category, Request $request, MenuItem $menu)
    {
        $menu['admin']['survey']->addChild($category->getName(), [
            'route' => 'admin_survey_edit',
            'routeParameters' => ['id' => $category->getId()]
        ]);

        $form = $this->createForm(CategoryType::class, $category, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_survey_home');
        }
        return $this->render('admin/survey/index/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category
        ]);
    }
}
