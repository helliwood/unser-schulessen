<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-04-04
 * Time: 16:16
 */

namespace App\Controller\MasterData;

use App\Controller\AbstractController;
use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use App\Form\UserHasSchoolType;
use App\Repository\UserHasSchoolRepository;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/master_data/members", name="master_data_members_")
 * @IsGranted("ROLE_USER")
 */
class MemberController extends AbstractController
{
    /**
     * @Route("/", name="list")
     * @param Request $request
     * @return JsonResponse|Response
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function list(Request $request)
    {
        /** @var UserHasSchoolRepository $sr */
        $uhsr = $this->getDoctrine()->getRepository(UserHasSchool::class);

        if ($request->isMethod(Request::METHOD_POST)) {
            $em = $this->getDoctrine()->getManager();
            if ($this->getStateCountry() !== 'rp') {
                if ($this->isGranted(User::ROLE_FOOD_COMMISSIONER) ||
                    $this->isGranted(User::ROLE_SCHOOL_AUTHORITIES_ACTIVE)) {
                    switch ($request->get('action', null)) {
                        case "delete_invitation":
                            $user = $em->getRepository(User::class)->find($request->get('user_id', null));
                            $userHasSchool = $em->getRepository(UserHasSchool::class)
                                ->findOneBy(['user' => $user, 'school' => $this->getUser()->getCurrentSchool()]);
                            $em->remove($userHasSchool);
                            $em->flush();
                            break;

                        case "block_user":
                            $user = $em->getRepository(User::class)->find($request->get('user_id', null));
                            $userHasSchool = $em->getRepository(UserHasSchool::class)
                                ->findOneBy(['user' => $user, 'school' => $this->getUser()->getCurrentSchool()]);

                            if ($userHasSchool->getState() === UserHasSchool::STATE_ACCEPTED) {
                                $userHasSchool->setState(UserHasSchool::STATE_BLOCKED);
                            } elseif ($userHasSchool->getState() === UserHasSchool::STATE_BLOCKED) {
                                $userHasSchool->setState(UserHasSchool::STATE_ACCEPTED);
                            }

                            $em->persist($userHasSchool);
                            $em->flush();
                            break;
                    }
                }
            } else {
                $user = $em->getRepository(User::class)->find($request->get('user_id', null));
                $school = $this->getSchoolByRequest($request);
                $userHasSchool = $em->getRepository(UserHasSchool::class)
                    ->findOneBy(['user' => $user, 'school' => $school]);
                if ((
                        $this->getUser()->getRoleByCurrentSchool() === User::ROLE_MENSA_AG ||
                        $this->getUser()->getRoleByCurrentSchool() === User::ROLE_CONSULTANT ||
                        \in_array(User::ROLE_ADMIN, $this->getUser()->getRoles())
                    )
                    && $userHasSchool->getRole() !== User::ROLE_CONSULTANT
                ) {
                    switch ($request->get('action', null)) {
                        case "delete_invitation":
                            if ($userHasSchool->getRole() !== User::ROLE_CONSULTANT) {
                                $em->remove($userHasSchool);
                                $em->flush();
                            }
                            break;

                        case "block_user":
                            if ($userHasSchool->getState() === UserHasSchool::STATE_ACCEPTED) {
                                $userHasSchool->setState(UserHasSchool::STATE_BLOCKED);
                            } elseif ($userHasSchool->getState() === UserHasSchool::STATE_BLOCKED) {
                                $userHasSchool->setState(UserHasSchool::STATE_ACCEPTED);
                            }

                            $em->persist($userHasSchool);
                            $em->flush();
                            break;
                    }
                }
            }
        }

        return new JsonResponse($uhsr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'name'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1),
            [UserHasSchool::STATE_ACCEPTED, UserHasSchool::STATE_REQUESTED]
        ));
    }

    /**
     * @param Request $request
     * @return School|\App\Repository\User[]|object[]|null
     * @throws Exception
     */
    protected function getSchoolByRequest(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $school_id = $request->get('school_id', null);

        return \is_null($school_id) ? $this->getUser()->getCurrentSchool() : $em->getRepository(School::class)->findBy(['id' => $school_id]);
    }

    /**
     * @Route("/list-inactive", name="list_inactive")
     * @param Request $request
     * @return JsonResponse|Response
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function listInactive(Request $request)
    {
        /** @var UserHasSchoolRepository $sr */
        $uhsr = $this->getDoctrine()->getRepository(UserHasSchool::class);

        if ($request->isMethod(Request::METHOD_POST)) {
            $em = $this->getDoctrine()->getManager();

            if ($this->isGranted(User::ROLE_FOOD_COMMISSIONER) ||
                $this->isGranted(User::ROLE_SCHOOL_AUTHORITIES_ACTIVE)) {
                switch ($request->get('action', null)) {
                    case "delete_invitation":
                        $user = $em->getRepository(User::class)->find($request->get('user_id', null));
                        $userHasSchool = $em->getRepository(UserHasSchool::class)
                            ->findOneBy(['user' => $user, 'school' => $this->getUser()->getCurrentSchool()]);
                        $em->remove($userHasSchool);
                        $em->flush();
                        break;

                    case "block_user":
                        $user = $em->getRepository(User::class)->find($request->get('user_id', null));
                        $userHasSchool = $em->getRepository(UserHasSchool::class)
                            ->findOneBy(['user' => $user, 'school' => $this->getUser()->getCurrentSchool()]);

                        if ($userHasSchool->getState() === UserHasSchool::STATE_ACCEPTED) {
                            $userHasSchool->setState(UserHasSchool::STATE_BLOCKED);
                        } elseif ($userHasSchool->getState() === UserHasSchool::STATE_BLOCKED) {
                            $userHasSchool->setState(UserHasSchool::STATE_ACCEPTED);
                        }

                        $em->persist($userHasSchool);
                        $em->flush();
                        break;
                }
            }
        }

        return new JsonResponse($uhsr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'name'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1),
            [UserHasSchool::STATE_BLOCKED, UserHasSchool::STATE_REJECTED]
        ));
    }

    /**
     * @Route("/inactive", name="inactive")
     * @Security("is_granted('ROLE_FOOD_COMMISSIONER') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function index()
    {
        return $this->render('master_data/members/inactive.html.twig', [
            'school' => $this->getUser()->getCurrentSchool()
        ]);
    }

    /**
     * @Route("/new", name="new")
     * @Security("is_granted('ROLE_FOOD_COMMISSIONER') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param MenuItem $menu
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function new(MenuItem $menu, Request $request)
    {
        $school = $this->getUser()->getCurrentSchool();

        $menu['master_data']->addChild('Neues Mitglied', [
            'route' => 'master_data_members_new'
        ]);

        $em = $this->getDoctrine()->getManager();
        /** @var UserHasSchoolRepository $ur */
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
            return $this->redirectToRoute('master_data_home');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $em->getRepository(User::class)->loadUserByUsername($form->get('email')->getData());
            if (\is_null($user)) {
                $user = new User();
                $user->setEmail($form->get('email')->getData());
            }
            $userHasSchool->setUser($user);
            $em->persist($userHasSchool);
            $em->flush();

            if ($form->has('sendInvitation') && $form->get('sendInvitation')->getData() === true) {
                $this->sendInvitationMail($userHasSchool);
            }

            $this->getSuccessMessage();

            return $this->redirectToRoute('master_data_home');
        }
        return $this->render('master_data/members/new.html.twig', [
            'form' => $form->createView(),
            'userHasSchool' => $userHasSchool
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @Security("is_granted('ROLE_FOOD_COMMISSIONER') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param User $user
     * @param MenuItem $menu
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function edit(User $user, MenuItem $menu, Request $request)
    {
        $menu['master_data']->addChild($user->getDisplayName(), [
            'route' => 'master_data_members_edit',
            'routeParameters' => ['id' => $user->getId()]
        ]);

        $em = $this->getDoctrine()->getManager();
        /** @var UserHasSchoolRepository $ur */
        $uhsr = $em->getRepository(UserHasSchool::class);

        $userHasSchool = $uhsr->findOneBy(['user' => $user, 'school' => $this->getUser()->getCurrentSchool()]);
        if (! $userHasSchool || ($this->getStateCountry() === 'rp' && $userHasSchool->getRole() === User::ROLE_CONSULTANT)) {
            $this->getErrorMessage('Sie haben nicht die Berechtigung um den Nutzer zu bearbeiten!');

            return $this->redirectToRoute('master_data_home');
        }

        $form = $this->createForm(UserHasSchoolType::class, $userHasSchool);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('master_data_home');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($userHasSchool);
            $em->flush();

            if ($form->has('sendInvitation') && $form->get('sendInvitation')) {
                $this->sendInvitationMail($userHasSchool);
            }

            $this->getSuccessMessage();

            return $this->redirectToRoute('master_data_home');
        }
        return $this->render('master_data/members/edit.html.twig', [
            'form' => $form->createView(),
            'userHasSchool' => $userHasSchool
        ]);
    }

    /**
     * @param UserHasSchool $userHasSchool
     * @throws TransportExceptionInterface
     */
    protected function sendInvitationMail(UserHasSchool $userHasSchool): void
    {
        $template = 'invitation' . ($this->stateCountry === 'he' ? '_he': '');

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
     * @Route("/{school}/change-password/{id}", name="change_password")
     * @param School $school
     * @param User $user
     * @param MenuItem $menu
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return RedirectResponse|Response
     */
    public function changePassword(
        School $school,
        User $user,
        MenuItem $menu,
        Request $request,
        UserPasswordEncoderInterface $encoder
    ) {
        $menu['admin']['school']->addChild($school->getName(), [
            'route' => 'admin_school_show',
            'routeParameters' => ['id' => $school->getId()]
        ])->addChild($user->getDisplayName(), [
            'route' => 'admin_school_members_change_password',
            'routeParameters' => ['id' => $user->getId(), 'school' => $school->getId()]
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

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            $this->getSuccessMessage();
            return $this->redirectToRoute('admin_school_show', ['id' => $school->getId()]);
        }

        return $this->render('admin/member/change_password.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
}
