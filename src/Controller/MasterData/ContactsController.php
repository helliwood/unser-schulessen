<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-04-04
 * Time: 16:16
 */

namespace App\Controller\MasterData;

use App\Controller\AbstractController;
use App\Entity\Person;
use App\Form\ContactType;
use App\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Knp\Menu\MenuItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/master_data/contacts', name: 'master_data_contacts_')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_USER')]
class ContactsController extends AbstractController
{
    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse|Response
     * @throws NonUniqueResultException
     */
    #[Route(path: '/', name: 'list')]
    public function list(Request $request, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        /** @var PersonRepository $sr */
        $pr = $entityManager->getRepository(Person::class);

        $ajax = $pr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'name'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1)
        );

        \array_unshift(
            $ajax["items"],
            (new Person())->setId(0)
                ->setLastName($translator->trans("contact_name"))
            ->setEmail($translator->trans("contact_email"))
        );

        return new JsonResponse($ajax);
    }

    /**
     * @param MenuItem $menu
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws Exception
     */
    #[Route(path: '/new', name: 'new')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_FOOD_COMMISSIONER') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')"))]
    public function new(MenuItem $menu, Request $request)
    {
        $school = $this->getUser()->getCurrentSchool();

        $menu['master_data']->addChild('Neuer Kontakt', [
            'route' => 'master_data_contacts_new'
        ]);

        $em = $this->getDoctrine()->getManager();

        $person = new Person();
        $person->setSchool($school);

        $form = $this->createForm(ContactType::class, $person);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('master_data_home');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($person);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('master_data_home');
        }
        return $this->render('master_data/contacts/new.html.twig', [
            'form' => $form->createView(),
            'person' => $person
        ]);
    }

    /**
     * @param Person $person
     * @param MenuItem $menu
     * @param Request $request
     * @return RedirectResponse|Response
     */
    #[Route(path: '/edit/{id}', name: 'edit')]
    #[\Symfony\Component\Security\Http\Attribute\IsGranted(new \Symfony\Component\ExpressionLanguage\Expression("is_granted('ROLE_FOOD_COMMISSIONER') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')"))]
    public function edit(Person $person, MenuItem $menu, Request $request)
    {
        $menu['master_data']->addChild($person->getDisplayName(), [
            'route' => 'master_data_contacts_edit',
            'routeParameters' => ['id' => $person->getId()]
        ]);

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(ContactType::class, $person);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('master_data_home');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($person);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('master_data_home');
        }
        return $this->render('master_data/contacts/edit.html.twig', [
            'form' => $form->createView(),
            'person' => $person
        ]);
    }

    /**
     * @param Person $person
     * @param MenuItem $menu
     * @param Request $request
     * @return RedirectResponse|Response
     */
    #[Route(path: '/show/{id}', name: 'show')]
    public function show(Person $person, MenuItem $menu, Request $request)
    {
        $menu['master_data']->addChild($person->getDisplayName(), [
            'route' => 'master_data_contacts_show',
            'routeParameters' => ['id' => $person->getId()]
        ]);

        $form = $this->createForm(ContactType::class, $person, ['disabled' => true]);

        if ($request->request->has('close')) {
            return $this->redirectToRoute('master_data_home');
        }
        return $this->render('master_data/contacts/show.html.twig', [
            'form' => $form->createView(),
            'person' => $person
        ]);
    }
}
