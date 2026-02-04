<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-27
 * Time: 09:23
 */

namespace App\Controller;

use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use App\Form\ActivateType;
use App\Form\CreateNewPasswordType;
use App\Form\PasswordFormType;
use App\Form\ProfileType;
use App\Form\ResetPasswordType;
use App\Form\TempPasswordChangeType;
use App\Repository\UserHasSchoolRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public string $logo;

    public function __construct()
    {
        $logo = "/img/logo_" . $_ENV["APP_STATE_COUNTRY"] . ".jpg";

        $this->logo = \file_exists("../public" . $logo) ? $logo : "/img/logo.svg";
    }

    /**
     * @Route("/login", name="login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirect('/');
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'logo' => $this->logo,
        ]);
    }

    /**
     * @Route("/reset", name="reset")
     * @param Request $request
     * @param MailerInterface $mailer
     * @return Response
     * @throws TransportExceptionInterface
     */
    public function reset(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $user = $em->getRepository(User::class)->loadUserByUsername($form->getData()['email']);
            if (\is_object($user)) {
                // create token expiration date
                $hashCreatedAt = \date_create(\date('Y-m-d H:i:s'));
                $hashExpDate = $hashCreatedAt->modify('+ 1 day');
                $user->setHashExpirationDate($hashExpDate);

                // create pw reset token
                $hash = \md5($user->getEmail() . $user->getCreatedAt()->format('Y-m-d H:i:s'));
                $user->setResetPasswordHash($hash);

                $em->persist($user);
                $em->flush();


                $email = (new TemplatedEmail())
                    ->subject('Unser Schulessen - Setzen Sie Ihr Passwort zurück. ')
                    ->from(new Address('bb@unser-schulessen.de', 'Unser Schulessen'))
                    ->to($form->getData()['email'])
                    ->htmlTemplate('emails/reset_password.html.twig')
                    ->context(
                        [
                            'name' => $user->getDisplayName(),
                            'user' => $user,
                            'link' => $this->generateUrl('login_token', [
                                'token' => \md5(
                                    $user->getEmail() . $user->getCreatedAt()->format('Y-m-d H:i:s')
                                )
                            ], UrlGeneratorInterface::ABSOLUTE_URL)
                        ]
                    );

                $mailer->send($email);
            }
            $this->getSuccessMessage('Eine Mail mit dem Aktivierungslink für ein neues Passwort wurde an Sie verschickt.');
            return $this->redirect('login');
        }
        return $this->render('security/reset.html.twig', [
            'form' => $form->createView(),
            'logo' => $this->logo,
        ]);
    }

    /**
     * @Route("/login/{token}", name="login_token")
     * @param string $token
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     * @throws NonUniqueResultException
     */
    public function createNewPassword(string $token, Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findUserByToken($token);

        $form = $this->createForm(CreateNewPasswordType::class, $user);
        $form->handleRequest($request);

        $today = \date_create(\date('Y-m-d'));

        // Check if hash is expired & if it belongs to an user
        if (\is_object($user) && ! \is_null($user->getHashExpirationDate()) && $today <= $user->getHashExpirationDate()) {
            if ($form->isSubmitted() && $form->isValid()) {
                //save new password
                $encoded = $encoder->encodePassword($user, $user->getPassword());
                $user->setPassword($encoded);
                $user->setResetPasswordHash(null);
                $user->setHashExpirationDate(null);

                $em->persist($user);
                $em->flush();

                $this->getSuccessMessage('Ihr neues Passwort wurde gespeichert. Sie können sich jetzt damit einloggen.');

                return $this->redirectToRoute('login');
            }

            return $this->render('security/create_password.html.twig', [
                'form' => $form->createView(),
                'logo' => $this->logo,
            ]);
        }

        $this->getErrorMessage(
            'Zeit zum Passwort zurücksetzen überschritten oder falschen Link in Adressleiste eingegeben. 
            Überprüfen sie den Link oder lassen Sie einen neuen generieren.'
        );
        return $this->redirectToRoute('login');
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/profile", name="profile")
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     */
    public function profile(Request $request, MenuItem $menu): Response
    {
        $menu['dashboard']->addChild("Profil", [
            'route' => 'profile'
        ]);

        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user->getPerson(), []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getSuccessMessage('Ihr Profil wurde erfolgreich gespeichert');
            /*
            if (! empty($form->get('profile')->get('new_password')->getData())) {
                $user->setPassword($encoder->encodePassword($user, $form->get('user')->get('new_password')->getData()));
            }*/

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('security/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/change-password", name="change_password")
     * @param Request $request
     * @param MenuItem $menu
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function changePassword(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(PasswordFormType::class, $user, []);
        $form->handleRequest($request);

        $oldPassword = $form->get('oldPassword')->getData();

        if ($form->isSubmitted() && $form->isValid() && $encoder->isPasswordValid($user, $oldPassword)) {
            $newPassword = $encoder->encodePassword($user, $form->get('newPassword')->getData());

            $user->setPassword($newPassword);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->getSuccessMessage('Ihr Passwort wurde erfolgreich gespeichert!');

            return $this->redirectToRoute('home');
        } elseif (! \is_null($oldPassword) && ! $encoder->isPasswordValid($user, $oldPassword)) {
            $this->getErrorMessage('Sie haben ihr altes Passwort falsch eingegeben!');
        }

        return $this->render('security/change_password.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'logo' => $this->logo,
        ]);
    }

    /**
     * @Route("/invitation/{token}/{user}/{school}", name="invitation")
     * @param string $token
     * @param User $user
     * @param School $school
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     * @throws NonUniqueResultException
     */
    public function invitation(
        string $token,
        User $user,
        School $school,
        Request $request,
        UserPasswordEncoderInterface $encoder
    ): Response {
        /** @var UserRepository $ur */
        $ur = $this->getDoctrine()->getRepository(User::class);
        /** @var UserHasSchoolRepository $uhsr */
        $uhsr = $this->getDoctrine()->getRepository(UserHasSchool::class);

        $userByToken = $ur->findUserByToken($token);
        if ($user !== $userByToken) {
            throw $this->createAccessDeniedException('User not match!');
        }
        $uhs = $uhsr->find(['user' => $userByToken, 'school' => $school]);

        $error = null;
        if (\is_null($uhs)) {
            $error = 'Die Einladung wurde zurückgezogen!';
        } elseif ($uhs->getState() === UserHasSchool::STATE_ACCEPTED) {
            $error = 'Sie haben die Einladung bereits angenommen!';
        } elseif ($uhs->getState() === UserHasSchool::STATE_REJECTED) {
            $error = 'Sie haben die Einladung bereits abgelehnt!';
        } elseif ($user->getState() === User::STATE_BLOCKED) {
            $error = 'Sie wurden blockiert!';
        }

        if ($error) {
            $this->getErrorMessage($error);
            return $this->redirect('/login');
        }

        if ($user->getState() === User::STATE_NOT_ACTIVATED) {
            $form = $this->createForm(ActivateType::class, $user, []);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setPassword($encoder->encodePassword($user, $user->getPassword()));
                $user->setState(User::STATE_ACTIVE);

                $uhs->setRespondedAt(new \DateTime());
                $uhs->setState(UserHasSchool::STATE_ACCEPTED);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->getSuccessMessage('Ihr Account wurde erfolgreich aktiviert.');

                return $this->redirect('/login');
            }
        } elseif ($user->getState() === User::STATE_ACTIVE) {
            // Form mit Buttons zum an und ablehnen rendern
            if ($this->getUser()) {
                return $this->redirect('/');
            }
        }

        return $this->render('security/invitation.html.twig', [
            'user' => $user,
            'school' => $school,
            'form' => isset($form) ? $form->createView() : null,
            'logo' => $this->logo,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/change-temp-password", name="change_temp_password")
     * @param Request $request
     * @param MenuItem $menu
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function changeTempPassword(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(TempPasswordChangeType::class, $user, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $encoder->encodePassword($user, $user->getNewPassword());
            $user->setPassword($newPassword);
            $user->setTempPassword(false);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->getSuccessMessage('Ihr Passwort wurde erfolgreich gespeichert!');

            return $this->redirectToRoute('home');
        }

        return $this->render('security/change_temp_password.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'logo' => $this->logo,
        ]);
    }
}
