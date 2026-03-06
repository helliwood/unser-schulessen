<?php
/**
 * Created by PhpStorm.
 * User: melchior
 * Date: 2020-03-19
 * Time: 13:06
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Person;
use App\Entity\User;
use App\Entity\UserHasSchool;
use App\Form\ChangeEmailType;
use App\Form\EmployeeType;
use App\Form\TempPasswordType;
use App\Repository\UserHasSchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Knp\Menu\MenuItem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route(path: '/admin/employee', name: 'admin_employee_')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
class EmployeeController extends AbstractController
{

    /**
     * Employee Controller constructor.
     * @param MenuItem $menu
     */
    public function __construct(MenuItem $menu)
    {
        $menu['employee'];
    }

    /**
     * @param Request $request
     * @return JsonResponse|Response
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    #[Route(path: '/', name: 'home')]
    public function index(Request $request)
    {

        if ($request->isXmlHttpRequest()) {
            /** @var UserHasSchoolRepository $ur */
            $ur = $this->getDoctrine()->getRepository(User::class);

            return new JsonResponse($ur->findEmployees4Ajax(
                $request->query->get('sort', 'name'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        return $this->render('admin/employee/index.html.twig');
    }

    /**
     * @param int      $userId
     * @param MenuItem $menu
     * @param Request  $request
     * @return Response
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    #[Route(path: '/{userId}/schools', name: 'list_schools')]
    public function listSchools(int $userId, MenuItem $menu, Request $request): Response
    {
        $ur = $this->getDoctrine()->getRepository(User::class);
        $user = $ur->findBy(['id' => $userId])[0];

        /** @var UserHasSchoolRepository $uhsr */
        $uhsr = $this->getDoctrine()->getRepository(UserHasSchool::class);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($uhsr->findSchools4Ajax(
                $user,
                $request->query->get('sort', 'name'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        $menu['admin']['employee']->addChild($user->getDisplayName(), [
            'route' => 'admin_employee_list_schools',
            'routeParameters' => [
                'userId' => $userId
            ]
        ]);

        return $this->render('admin/employee/user_has_schools.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @param EntityManagerInterface       $em
     * @param UserPasswordHasherInterface $encoder
     * @param MenuItem                     $menu
     * @param Request                      $request
     * @return RedirectResponse|Response
     * @throws Exception
     */
    #[Route(path: '/new', name: 'new')]
    public function new(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $encoder,
        MenuItem $menu,
        Request $request
    ) {
        $menu['admin']['employee']['admin_employee_new'];

        $person = new Person();
        $user = new User();
        $user->setPerson($person);

        $form = $this->createForm(EmployeeType::class, $user, []);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            $this->getErrorMessage('Bearbeitung abgebrochen!');

            return $this->redirectToRoute('admin_employee_home');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleConsultantRequest($user, $form);

            $this->handlePasswordRequest($user, $form, $encoder);

            $user
                ->setEmployee(1)
                ->setState(1)
                ->getPerson()->setEmail($form['email']->getData());

            $em->persist($user);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_employee_home');
        }
        return $this->render('admin/employee/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @param EntityManagerInterface       $em
     * @param UserPasswordHasherInterface $encoder
     * @param User                         $user
     * @param MenuItem                     $menu
     * @param Request                      $request
     * @return RedirectResponse|Response
     * @throws Exception
     */
    #[Route(path: '/{user}/edit', name: 'edit_user')]
    public function editUser(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $encoder,
        User $user,
        MenuItem $menu,
        Request $request
    ) {
        $menu['admin']['employee']->addChild($user->getDisplayName(), [
            'route' => 'admin_employee_edit_user',
            'routeParameters' => [
                'user' => $user->getId()
            ]
        ]);

        $is_consultant = \in_array('ROLE_CONSULTANT', $user->getRoles());


        $form = $this->createForm(EmployeeType::class, $user, ['edit_form' => true, 'is_consultant' => $is_consultant]);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            $this->getErrorMessage('Bearbeitung abgebrochen!');

            return $this->redirectToRoute('admin_employee_home');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleConsultantRequest($user, $form);

            $this->handlePasswordRequest($user, $form, $encoder);

            $em->persist($user);
            $em->flush();
            $this->getSuccessMessage();

            return $this->redirectToRoute('admin_employee_home', []);
        }

        return $this->render('admin/employee/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @param User                         $user
     * @param MenuItem                     $menu
     * @param Request                      $request
     * @param UserPasswordHasherInterface $encoder
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/{user}/change-password', name: 'change_password')]
    public function changePassword(
        User $user,
        MenuItem $menu,
        Request $request,
        UserPasswordHasherInterface $encoder
    ) {
        $menu['admin']['employee']->addChild($user->getDisplayName(), [
            'route' => 'admin_employee_change_password',
            'routeParameters' => [
                'user' => $user->getId()
            ]
        ]);

        $form = $this->createForm(TempPasswordType::class, $user);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('admin_employee_home', []);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // save temp password
            $encoded = $encoder->hashPassword($user, $user->getNewPassword());
            $user->setPassword($encoded);
            $user->setTempPassword(true);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            $this->getSuccessMessage();
            return $this->redirectToRoute('admin_employee_home', []);
        }

        return $this->render('admin/employee/change_password.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }


    /**
     * @param User     $user
     * @param MenuItem $menu
     * @param Request  $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/{user}/change-email', name: 'change_email')]
    public function changeEmail(
        User $user,
        MenuItem $menu,
        Request $request
    ) {
        $menu['admin']['employee']->addChild($user->getDisplayName(), [
            'route' => 'admin_employee_change_email',
            'routeParameters' => [
                'user' => $user->getId()
            ]
        ]);

        $form = $this->createForm(ChangeEmailType::class, $user, ['use_password' => false]);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('admin_employee_home', []);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            $this->getSuccessMessage();
            return $this->redirectToRoute('admin_employee_home', []);
        }

        return $this->render('admin/employee/change_email.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @param User $user
     * @param Form $form
     */
    private function handleConsultantRequest(User $user, Form $form): void
    {
        if (isset($form['roles']) &&
            \in_array(User::ROLE_CONSULTANT, $form['roles']->getData())) {
            $user->setState(1);
        }
    }

    /**
     * @param User                         $user
     * @param Form                         $form
     * @param UserPasswordHasherInterface $encoder
     */
    private function handlePasswordRequest(User &$user, Form $form, UserPasswordHasherInterface $encoder): void
    {
        if (! \is_null($form['newPassword']->getData())) {
            $user->setPassword($encoder->hashPassword($user, $form['newPassword']->getData()));
        }
    }
}
