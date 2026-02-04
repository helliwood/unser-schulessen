<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-26
 * Time: 14:30
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Questionnaire;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/category", name="admin_category_")
 * @IsGranted("ROLE_ADMIN")
 */
class CategoryController extends AbstractController
{

    /**
     * @Route("/list/{id}", name="list")
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Throwable
     */
    public function list(Request $request, Category $category)
    {
        /** @var CategoryRepository $cr */
        $cr = $this->getDoctrine()->getRepository(Category::class);

        if ($request->isMethod(Request::METHOD_POST)) {
            $em = $this->getDoctrine()->getManager();
            $postCategory = $cr->find($request->get('category_id', null));
            if ($postCategory) {
                switch ($request->get('action', null)) {
                    case "up":
                        $cr->up($postCategory->getId(), $postCategory->getParent()->getId());
                        break;
                    case "down":
                        $cr->down($postCategory->getId(), $postCategory->getParent()->getId());
                        break;
                    case "delete_category":
                        $em->remove($postCategory);
                        $em->flush();
                        break;
                }
                $postCategory->getQuestionnaire()->reorderCategories();
                $em->flush();
            }
        }

        return new JsonResponse($cr->findByParent4Ajax(
            $category,
            $request->query->getAlnum('sort', 'date'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1)
        ));
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
        $menu['admin']['questionnaire']->addChild($category->getQuestionnaire()->getName(), [
            'route' => 'admin_questionnaire_show',
            'routeParameters' => ['id' => $category->getQuestionnaire()->getId()]
        ])->addChild($category->getName(), [
            'route' => 'admin_category_edit',
            'routeParameters' => ['id' => $category->getId()]
        ]);

        $form = $this->createForm(CategoryType::class, $category, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            $this->getSuccessMessage();

            return $category->getParent() ?
                $this->redirectToRoute('admin_questionnaire_category_questions_home', [
                    'id' => $category->getParent()->getId()]) :
                $this->redirectToRoute('admin_questionnaire_show', [
                    'id' => $category->getQuestionnaire()->getId()]);
        }
        return $this->render('admin/category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category
        ]);
    }

    /**
     * Wegen einem Bug in Symfony funktioniert weder {parent?} noch defaults={"parent":null}, deswegen wird
     * ein String als default Wert benutzt. Ansonsten ist parent NIE null (zumindestens dann nicht, wenn id = 1 ist)
     *
     * @Route("/new/{id}/{parent}", name="new", defaults={"parent":"bug_string_als_default"}, requirements={"parent"="\d+"})
     * @param Questionnaire $questionnaire
     * @param Category|null $parent
     * @param Request       $request
     * @param MenuItem      $menu
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function new(Questionnaire $questionnaire, ?Category $parent, Request $request, MenuItem $menu)
    {
        $menu = $menu['admin']['questionnaire']->addChild($questionnaire->getName(), [
            'route' => 'admin_questionnaire_show',
            'routeParameters' => ['id' => $questionnaire->getId()]
        ]);

        if ($parent) {
            if ($questionnaire !== $parent->getQuestionnaire()) {
                throw new \Exception('Parent don\'t fit to questionnaire!');
            }
            $menu = $menu->addChild($parent->getName(), [
                'route' => 'admin_questionnaire_category_questions_home',
                'routeParameters' => ['id' => $parent->getId()]
            ]);
        }

        $menu->addChild('Neue Kategorie', [
            'route' => 'admin_category_new',
            'routeParameters' => ['id' => $questionnaire->getId()]
        ]);

        $em = $this->getDoctrine()->getManager();
        $category = new Category();
        if ($parent) {
            $category->setParent($parent);
            $category->setOrder($parent->getChildren()->count() + 1);
        } else {
            $category->setOrder($questionnaire->getCategories()->count() + 1);
        }
        $category->setQuestionnaire($questionnaire);

        $form = $this->createForm(CategoryType::class, $category, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->getSuccessMessage();

            return ! \is_null($parent) ?
                $this->redirectToRoute('admin_questionnaire_category_questions_home', [
                    'id' => $parent->getId()]) :
                $this->redirectToRoute('admin_questionnaire_show', [
                    'id' => $questionnaire->getId()]);
        }
        return $this->render('admin/category/new.html.twig', [
            'form' => $form->createView(),
            'questionnaire' => $questionnaire
        ]);
    }
}
