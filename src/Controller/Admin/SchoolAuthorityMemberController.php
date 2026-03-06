<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\SchoolAuthority;
use App\Entity\User;
use App\Form\SchoolAuthorityMemberType;
use App\Repository\UserRepository;
use Knp\Menu\MenuItem;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/admin/school-authority/members', name: 'admin_school_authority_members_')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
class SchoolAuthorityMemberController extends AbstractController
{
    /**
     * @param SchoolAuthority $schoolAuthority
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(path: '/{id}/', name: 'list')]
    public function list(SchoolAuthority $schoolAuthority, Request $request): JsonResponse
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        return new JsonResponse($userRepository->findBySchoolAuthority4Ajax(
            $schoolAuthority,
            $request->query->get('sort', 'displayName'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('perPage', 10)
        ));
    }

    /**
     * @param SchoolAuthority $schoolAuthority
     * @param MenuItem $menu
     * @param Request $request
     * @return RedirectResponse|Response
     */
    #[Route(path: '/{id}/new', name: 'new')]
    public function new(SchoolAuthority $schoolAuthority, MenuItem $menu, Request $request)
    {
        $menu['admin']['school_authority']->addChild($schoolAuthority->getName(), [
            'route' => 'admin_school_authority_show',
            'routeParameters' => ['id' => $schoolAuthority->getId()]
        ])->addChild('Mitglied einladen', [
            'route' => 'admin_school_authority_members_new',
            'routeParameters' => ['id' => $schoolAuthority->getId()]
        ]);

        $em = $this->getDoctrine()->getManager();
        /** @var UserRepository $userRepository */
        $userRepository = $em->getRepository(User::class);

        $form = $this->createForm(SchoolAuthorityMemberType::class);
        $form->handleRequest($request);

        if ($form->get('email')->getData()) {
            $existingUser = $userRepository->findOneBy(['email' => $form->get('email')->getData()]);
            if ($existingUser) {
                $form->get('email')->addError(
                    new FormError('E-Mail ist bereits vergeben und kann nicht als Schulträger eingeladen werden!')
                );
            }
        }

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('admin_school_authority_show', ['id' => $schoolAuthority->getId()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();
            $user->setEmail($form->get('email')->getData());
            $user->setSchoolAuthority($schoolAuthority);
            $user->addRole(User::ROLE_SCHOOL_AUTHORITY);
            $em->persist($user);
            $em->flush();

            if ($form->has('sendInvitation') && $form->get('sendInvitation')->getData()) {
                $this->sendInvitationMail($user, $schoolAuthority);
            }

            $this->getSuccessMessage();
            return $this->redirectToRoute('admin_school_authority_show', ['id' => $schoolAuthority->getId()]);
        }

        return $this->render('admin/school_authority_member/new.html.twig', [
            'form' => $form->createView(),
            'schoolAuthority' => $schoolAuthority
        ]);
    }

    /**
     * @param SchoolAuthority $schoolAuthority
     * @param User $user
     * @param MenuItem $menu
     * @param Request $request
     * @return RedirectResponse|Response
     */
    #[Route(path: '/{id}/edit/{user}', name: 'edit')]
    public function edit(SchoolAuthority $schoolAuthority, User $user, MenuItem $menu, Request $request)
    {
        if ($user->getSchoolAuthority() !== $schoolAuthority) {
            throw $this->createAccessDeniedException('Benutzer gehört nicht zu diesem Schulträger.');
        }

        $menu['admin']['school_authority']->addChild($schoolAuthority->getName(), [
            'route' => 'admin_school_authority_show',
            'routeParameters' => ['id' => $schoolAuthority->getId()]
        ])->addChild($user->getDisplayName() . ' bearbeiten', [
            'route' => 'admin_school_authority_members_edit',
            'routeParameters' => ['id' => $schoolAuthority->getId(), 'user' => $user->getId()]
        ]);

        // Für die Bearbeitung verwenden wir nur das E-Mail-Feld
        $form = $this->createFormBuilder(['email' => $user->getEmail()])
            ->add('email', \Symfony\Component\Form\Extension\Core\Type\EmailType::class, [
                'label' => 'E-Mail',
                'required' => true,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('admin_school_authority_show', ['id' => $schoolAuthority->getId()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEmail($form->get('email')->getData());
            $this->getDoctrine()->getManager()->flush();
            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_school_authority_show', ['id' => $schoolAuthority->getId()]);
        }

        return $this->render('admin/school_authority_member/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'schoolAuthority' => $schoolAuthority
        ]);
    }

    /**
     * @param User $user
     * @param SchoolAuthority $schoolAuthority
     * @throws TransportExceptionInterface
     */
    protected function sendInvitationMail(User $user, SchoolAuthority $schoolAuthority): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@unser-schulessen.de', 'Unser Schulessen'))
            ->to($user->getEmail())
            ->subject('Einladung zu ' . $schoolAuthority->getName())
            ->htmlTemplate('emails/invitation_school_authority.html.twig')
            ->context([
                'user' => $user,
                'schoolAuthority' => $schoolAuthority,
                'link' => $this->generateUrl('invitation_school_authority', [
                    'token' => \md5(
                        $user->getEmail() . $user->getCreatedAt()->format('Y-m-d H:i:s')
                    ),
                    'user' => $user->getId(),
                    'schoolAuthority' => $schoolAuthority->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
        $this->mailer->send($email);
    }
}
