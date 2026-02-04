<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-04-04
 * Time: 16:16
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\PersonType;
use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use App\Form\ConsultantToSchoolType;
use App\Form\TempPasswordType;
use App\Form\UserHasSchoolType;
use App\Repository\UserHasSchoolRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/admin/school/members", name="admin_school_members_")
 * @IsGranted("ROLE_ADMIN")
 */
class MemberController extends AbstractController
{
    /**
     * @Route("/{id}/", name="list")
     * @param School $school
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function list(School $school, Request $request, EntityManagerInterface $em)
    {
        /** @var UserHasSchoolRepository $uhsr */
        $uhsr = $em->getRepository(UserHasSchool::class);

        if ($request->isMethod(Request::METHOD_POST)) {
            switch ($request->get('action', null)) {
                case "delete_invitation":
                    $user = $em->getRepository(User::class)->find($request->get('user_id', null));
                    $userHasSchool = $em->getRepository(UserHasSchool::class)
                        ->findOneBy(['user' => $user, 'school' => $school]);
                    $em->remove($userHasSchool);
                    $em->flush();
                    break;
            }
        }

        return new JsonResponse($uhsr->find4Ajax(
            $school,
            $request->query->get('sort', 'name'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1)
        ));
    }

    /**
     * @Route("/{id}/new", name="new")
     * @param School $school
     * @param MenuItem $menu
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function new(School $school, MenuItem $menu, Request $request, EntityManagerInterface $em)
    {
        $menu['admin']['school']->addChild($school->getName(), [
            'route' => 'admin_school_show',
            'routeParameters' => ['id' => $school->getId()]
        ])->addChild('Neues Mitglied', [
            'route' => 'admin_school_members_new',
            'routeParameters' => ['id' => $school->getId()]
        ]);

        /** @var UserHasSchoolRepository $uhsr */
        $uhsr = $em->getRepository(UserHasSchool::class);

        $userHasSchool = new UserHasSchool();
        $userHasSchool->setSchool($school);

        $form = $this->createForm(UserHasSchoolType::class, $userHasSchool, ['add_email_field' => true]);

        $form->handleRequest($request);
        if ($form->get('email')->getData()) {
            if ($uhsr->emailExistsInSchool($form->get('email')->getData(), $school)) {
                $form->get('email')->addError(new FormError('E-Mail bereits verknüpft!'));
            }
        }
        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var UserRepository $ur
             */
            $ur = $em->getRepository(User::class);
            $user = $ur->loadUserByUsername($form->get('email')->getData());
            if (\is_null($user)) {
                $user = new User();
                $user->setEmail($form->get('email')->getData());
            }

            $userHasSchool->setUser($user);
            $em->persist($userHasSchool);
            $em->flush();

            if ($form->has('sendInvitation') && $form->get('sendInvitation')->getData()) {
                $this->sendInvitationMail($userHasSchool);
            }

            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }
        return $this->render('admin/member/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/new-consultant", name="new_consultant")
     * @param School $school
     * @param MenuItem $menu
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function newConsultant(
        School $school,
        MenuItem $menu,
        Request $request,
        EntityManagerInterface $em
    ): \Symfony\Component\HttpFoundation\Response {
        $menu['admin']['school']
            ->addChild($school->getName(), [
                'route' => 'admin_school_show',
                'routeParameters' => ['id' => $school->getId()]
            ])
            ->addChild('Ernährungsberater zuweisen', [
                'route' => 'admin_school_members_new_consultant',
                'routeParameters' => ['id' => $school->getId()]
            ]);

        $userHasSchool = new UserHasSchool();
        $userHasSchool
            ->setSchool($school)
            ->setPersonType($em->find(PersonType::class, 'Gast'))
            ->setRole(User::ROLE_CONSULTANT)
            ->setState(UserHasSchool::STATE_CONSULTANT);


        $form = $this->createForm(ConsultantToSchoolType::class, $userHasSchool, ['school' => $school]);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($userHasSchool);
            $em->flush();

            $user = $userHasSchool->getUser();
            if (\is_null($user->getCurrentSchool())) {
                $user->setCurrentSchool($school);
            }

            $em->persist($userHasSchool);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }

        return $this->render('admin/member/new_consultant.html.twig', [
            'form' => $form->createView(),
            'userHasSchool' => $userHasSchool,
        ]);
    }

    /**
     * @Route("/{school}/edit/{id}", name="edit")
     * @param School $school
     * @param User $user
     * @param MenuItem $menu
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws TransportExceptionInterface
     */
    public function edit(School $school, User $user, MenuItem $menu, Request $request, EntityManagerInterface $em)
    {
        $menu['admin']['school']->addChild($school->getName(), [
            'route' => 'admin_school_show',
            'routeParameters' => ['id' => $school->getId()]
        ])->addChild($user->getDisplayName(), [
            'route' => 'admin_school_members_edit',
            'routeParameters' => ['id' => $user->getId(), 'school' => $school->getId()]
        ]);

        /** @var UserHasSchoolRepository $ur */
        $uhsr = $em->getRepository(UserHasSchool::class);

        $userHasSchool = $uhsr->findOneBy(['user' => $user, 'school' => $school]);


        $form = $this->createForm(UserHasSchoolType::class, $userHasSchool);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($userHasSchool);
            $em->flush();

            if ($form->has('sendInvitation') && $form->get('sendInvitation')->getData()) {
                $this->sendInvitationMail($userHasSchool);
            }

            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }
        return $this->render('admin/member/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @param UserHasSchool $userHasSchool
     * @throws TransportExceptionInterface
     */
    protected function sendInvitationMail(UserHasSchool $userHasSchool): void
    {
        $template = 'invitation' . ($this->stateCountry === 'he' ? '_he' : '');

        $email = (new TemplatedEmail())
            ->subject('Einladung zu "Unser Schulessen" von ' . $userHasSchool->getSchool()->getName() . ' aus ' .
                $userHasSchool->getSchool()->getAddress()->getCity())
            ->from(new Address('info@unser-schulessen.de', 'Unser Schulessen'))
            ->to($userHasSchool->getUser()->getEmail())
            ->htmlTemplate('emails/' . $template . '.html.twig')
            ->context(
                [
                    'user' => $userHasSchool->getUser(),
                    'school' => $userHasSchool->getSchool(),
                    'link' => $this->generateUrl('invitation', [
                        'token' => \md5(
                            $userHasSchool->getUser()->getEmail() .
                            $userHasSchool->getUser()->getCreatedAt()->format('Y-m-d H:i:s')
                        ),
                        'user' => $userHasSchool->getUser()->getId(),
                        'school' => $userHasSchool->getSchool()->getId()
                    ], UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            );

        $this->mailer->send($email);
    }

    /**
     * @Route("/{school}/change-password/{user}", name="change_password")
     * @param User $user
     * @param School $school
     * @param MenuItem $menu
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function changePassword(
        User $user,
        School $school,
        MenuItem $menu,
        Request $request,
        UserPasswordEncoderInterface $encoder,
        EntityManagerInterface $em
    ) {
        $menu['admin']['school']->addChild($school->getName(), [
            'route' => 'admin_school_show',
            'routeParameters' => [
                'id' => $school->getId(),
            ]
        ])->addChild($user->getDisplayName(), [
            'route' => 'admin_school_members_change_password',
            'routeParameters' => [
                'school' => $school->getId(),
                'user' => $user->getId(),
            ]
        ]);

        $form = $this->createForm(TempPasswordType::class, $user);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // save temp password
            $encoded = $encoder->encodePassword($user, $user->getNewPassword());
            $user->setPassword($encoded);
            $user->setTempPassword(true);


            $em->persist($user);
            $em->flush();
            $this->getSuccessMessage();
            $this->sendPasswordChangedMail($user->getUserHasSchool()->first());
            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }

        return $this->render('admin/member/change_password.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @param UserHasSchool $userHasSchool
     * @throws TransportExceptionInterface
     */
    protected function sendPasswordChangedMail(UserHasSchool $userHasSchool): void
    {
        $email = (new TemplatedEmail())
            ->subject('Bei "Unser Schulessen" wurde von ' . $userHasSchool->getSchool()->getName() .
                ' für Sie ein temporäres Passwort festgelegt. ')
            ->from(new Address('bb@unser-schulessen.de', 'Unser Schulessen'))
            ->to($userHasSchool->getUser()->getEmail())
            ->htmlTemplate('emails/change_password.html.twig')
            ->context(
                [
                    'user' => $userHasSchool->getUser(),
                    'school' => $userHasSchool->getSchool(),
                    'link' => $this->generateUrl('change_password', [
                        'token' => \md5(
                            $userHasSchool->getUser()->getEmail() .
                            $userHasSchool->getUser()->getCreatedAt()->format('Y-m-d H:i:s')
                        ),
                        'user' => $userHasSchool->getUser()->getId(),
                        'school' => $userHasSchool->getSchool()->getId()
                    ], UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            );

        $this->mailer->send($email);
    }
}
