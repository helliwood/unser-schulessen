<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\SchoolAuthority;
use App\Form\SchoolAuthorityType;
use App\Repository\SchoolAuthorityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/school-authority', name: 'admin_school_authority_')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
class SchoolAuthorityController extends AbstractController
{
    #[Route(path: '/', name: 'home')]
    public function index(): Response
    {
        return $this->render('admin/school_authority/index.html.twig');
    }

    #[Route(path: '/list', name: 'list')]
    public function list(SchoolAuthorityRepository $repository, Request $request): JsonResponse
    {
        return new JsonResponse($repository->find4Ajax(
            $request->query->get('sort', 'name'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 10),
            (string) $request->query->get('search', '')
        ));
    }

    #[Route(path: '/show/{id}', name: 'show')]
    public function show(SchoolAuthority $schoolAuthority, MenuItem $menu): Response
    {
        $menu['admin']['school_authority']->addChild($schoolAuthority->getName(), [
            'route' => 'admin_school_authority_show',
            'routeParameters' => ['id' => $schoolAuthority->getId()]
        ]);
        return $this->render('admin/school_authority/show.html.twig', [
            'schoolAuthority' => $schoolAuthority
        ]);
    }

    #[Route(path: '/new', name: 'new')]
    public function new(MenuItem $menu, Request $request, EntityManagerInterface $em): Response
    {
        $menu['admin']['school_authority']->addChild('Schulträger hinzufügen', [
            'route' => 'admin_school_authority_new'
        ]);
        $schoolAuthority = new SchoolAuthority();
        $form = $this->createForm(SchoolAuthorityType::class, $schoolAuthority);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($schoolAuthority);
            $em->flush();
            $this->getSuccessMessage();
            return $this->redirectToRoute('admin_school_authority_show', ['id' => $schoolAuthority->getId()]);
        }
        return $this->render('admin/school_authority/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/edit/{id}', name: 'edit')]
    public function edit(SchoolAuthority $schoolAuthority, MenuItem $menu, Request $request, EntityManagerInterface $em): Response
    {
        $menu['admin']['school_authority']->addChild($schoolAuthority->getName() . ' bearbeiten', [
            'route' => 'admin_school_authority_edit',
            'routeParameters' => ['id' => $schoolAuthority->getId()]
        ]);
        $form = $this->createForm(SchoolAuthorityType::class, $schoolAuthority);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($schoolAuthority);
            $em->flush();
            $this->getSuccessMessage();
            return $this->redirectToRoute('admin_school_authority_show', ['id' => $schoolAuthority->getId()]);
        }
        return $this->render('admin/school_authority/edit.html.twig', [
            'form' => $form->createView(),
            'schoolAuthority' => $schoolAuthority
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(SchoolAuthority $schoolAuthority, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($schoolAuthority);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }
}
