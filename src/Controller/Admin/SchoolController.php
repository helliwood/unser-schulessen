<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-04-04
 * Time: 16:16
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Address;
use App\Entity\Person;
use App\Entity\PersonType;
use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use App\Form\SchoolType;
use App\Repository\PersonRepository;
use App\Repository\SchoolRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/school", name="admin_school_")
 * @IsGranted("ROLE_ADMIN")
 */
class SchoolController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->render('admin/school/index.html.twig', [
            'districts_by' => Address::DISTRICTS_BY,
            'tag' => $request->get('tag'),
        ]);
    }

    /**
     * @Route("/list/{tag}", name="list", defaults={"tag"=null})
     * @param string|null $tag
     * @param Request $request
     * @return JsonResponse|Response
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getSchoolList(?string $tag, Request $request): JsonResponse
    {
        /** @var SchoolRepository $sr */
        $sr = $this->getDoctrine()->getRepository(School::class);

        if ($request->isMethod(Request::METHOD_POST)) {
            $em = $this->getDoctrine()->getManager();
            switch ($request->get('action', null)) {
                case "delete":
                    $school = $em->getRepository(School::class)->findOneBy(['id' => $request->get('id', null), 'miniCheck' => true]);
                    $em->remove($school);
                    $em->flush();
                    break;
            }
        }

        return new JsonResponse($sr->find4Ajax(
            $request->query->get('sort', 'name'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1),
            $tag
        ));
    }


    /**
     * @Route("/show/{id}", name="show")
     * @param School $school
     * @param Request $request
     * @param MenuItem $menu
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function show(School $school, Request $request, MenuItem $menu)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var PersonRepository $sr */
            $sr = $this->getDoctrine()->getRepository(Person::class);

            return new JsonResponse($sr->find4Ajax(
                $school,
                $request->query->get('sort', 'name'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        $menu['admin']['school']->addChild($school->getName(), [
            'route' => 'admin_school_show',
            'routeParameters' => ['id' => $school->getId()]
        ]);

        return $this->render('admin/school/show.html.twig', [
            'school' => $school
        ]);
    }

    /**
     * @Route("/new", name="new")
     * @param MenuItem $menu
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function new(MenuItem $menu, Request $request)
    {
        $menu['admin']['school']->addChild('Schule hinzufügen', [
            'route' => 'admin_school_new'
        ]);

        $em = $this->getDoctrine()->getManager();

        $school = new School();

        $form = $this->createForm(SchoolType::class, $school, ['is_admin_area' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($school);

            if ($school->getConsultant() !== null) {
                $uhs = new UserHasSchool();
                $uhs->setSchool($school)
                    ->setUser($school->getConsultant())
                    ->setState(1)
                    ->setRole(User::ROLE_CONSULTANT)
                    ->setPersonType($em->find(PersonType::class, PersonType::TYPE_GUEST));
                $em->persist($uhs);
            }
            $em->persist($school);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }
        return $this->render('admin/school/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @param School $school
     * @param MenuItem $menu
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function edit(School $school, MenuItem $menu, Request $request)
    {
        $menu['admin']['school']->addChild($school->getName() . ' bearbeiten', [
            'route' => 'admin_school_edit',
            'routeParameters' => ['id' => $school->getId()]
        ]);

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(SchoolType::class, $school, ['is_admin_area' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($school);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }
        return $this->render('admin/school/edit.html.twig', [
            'form' => $form->createView(),
            'school' => $school
        ]);
    }
}
