<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-07
 * Time: 10:20
 */

namespace App\Controller\QualityCircle;

use App\Controller\AbstractController;
use App\Entity\QualityCheck\Answer;
use App\Entity\QualityCheck\IdeaboxIcon;
use App\Entity\QualityCheck\Result;
use App\Entity\QualityCircle\ActionPlan;
use App\Entity\QualityCircle\ActionPlanNew;
use App\Entity\QualityCircle\ToDo;
use App\Entity\QualityCircle\ToDoItem;
use App\Entity\QualityCircle\ToDoNew;
use App\EventSubscriber\BeforeControllerInterface;
use App\Form\QualityCircle\ActionPlanCompleteType;
use App\Form\QualityCircle\ActionPlanType;
use App\Form\QualityCircle\ToDoOpenType;
use App\Form\QualityCircle\ToDoType;
use App\Repository\QualityCheck\AnswerRepository;
use App\Repository\QualityCircle\ToDoNewRepository;
use App\Repository\QualityCircle\ToDoRepository;
use App\Service\EmailNotificationService;
use App\Service\QualityCheckService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Dompdf\Dompdf;
use Exception;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/quality_circle/todo", name="quality_circle_todo_")
 * @IsGranted("ROLE_USER")
 */
class TodoController extends AbstractController implements BeforeControllerInterface
{
    /**
     * @var QualityCheckService
     */
    protected $qualityCheckService;

    /**
     * IndexController constructor.
     * @param MailerInterface       $mailer
     * @param QualityCheckService   $qualityCheckService
     * @param ParameterBagInterface $params
     */
    public function __construct(
        MailerInterface $mailer,
        QualityCheckService $qualityCheckService,
        ParameterBagInterface $params
    ) {
        parent::__construct($mailer, $params);

        $this->qualityCheckService = $qualityCheckService;
    }

    /**
     * @param ControllerEvent $event
     * @throws Exception
     */
    public function before(ControllerEvent $event): void
    {
        if (! $this->qualityCheckService->getLastResult()) {
            $this->getErrorMessage('Sie müssen erst den Qualitäts-Check bearbeiten.');
            $event->setController(function () {
                return $this->redirectToRoute('home');
            });
        }
    }

    /**
     * @Route("/list-closed", name="list_closed")
     * @param Request $request
     * @return JsonResponse
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function index(Request $request): JsonResponse
    {
        /** @var ToDoNewRepository $tdr */
        $tdr = $this->getDoctrine()->getRepository(ToDoNew::class);

        return new JsonResponse($tdr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'createdAt'),
            $request->query->getBoolean('sortDesc', true),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1),
            true
        ));
    }

    /**
     * @Route("/new/{resultId}", name="new")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_KITCHEN') or is_granted('ROLE_FOOD_COMMISSIONER')")
     * @param Request $request
     * @param QualityCheckService $qualityCheckService
     * @param int|null $resultId
     * @return Response
     * @throws ConnectionException
     * @throws NonUniqueResultException
     */
    public function new(Request $request, QualityCheckService $qualityCheckService, ?int $resultId = null): Response
    {
        if ($request->isXmlHttpRequest()) {
            $answers = $request->get('answers', null);
            if (! \is_array($answers) || \count($answers) <= 0 || \count($answers) > 3) {
                return new JsonResponse(['message' => 'Falsche Anzahl an Fragen.'], 419);
            }
            $em = $this->getDoctrine()->getManager();
            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();
            $conn->beginTransaction();
            try {
                /** @var AnswerRepository $ar */
                $ar = $this->getDoctrine()->getRepository(Answer::class);
                $result = null;
                foreach ($ar->findBy(['id' => $answers]) as $answer) {
                    if (\is_null($result)) {
                        $result = $answer->getResult();
                    }
                    if ($result !== $answer->getResult()) {
                        throw new Exception('Fragen von unterschiedlichen Ergebnissen.');
                    }
                    $todo = new ToDoNew();
                    $todo->setCreatedBy($this->getUser());
                    $todo->setName('ToDo vom ' . $todo->getCreatedAt()->format('d.m.Y'));
                    $todo->setAnswer($answer);
                    $todo->setSchool($this->getUser()->getCurrentSchool());
                    $em->persist($todo);
                }
                $em->flush();
                $conn->commit();
            } catch (\Throwable $e) {
                $conn->rollBack();
                return new JsonResponse(['message' => $e->getMessage()], 419);
            }
            $this->getSuccessMessage();

            return new JsonResponse([
                'answers' => $request->get('answers'),
                'redirect' => $this->generateUrl('quality_circle_home')
            ]);
        }
        /** @var Result $result */
        $result = $resultId ? $qualityCheckService->getResult($resultId) : $qualityCheckService->getLastResult();

        if (\is_null($result)) {
            throw new Exception('Ergebnis nicht gefunden. ID:' . $resultId);
        }
        $relatedResults = $this->qualityCheckService->getResultsByUsersCurrentSchool();

        return $this->render('quality_circle/todo/new.html.twig', [
            'relatedResults' => $relatedResults,
            'result' => $result,
            'school' => $this->getUser()->getCurrentSchool(),
            'flag_definitions' => $qualityCheckService->getFlagDefinitions()
        ]);
    }

    /**
     * @Route("/new-open", name="new_open")
     * @IsGranted("ROLE_MENSA_AG")
     * @param Request  $request
     * @param MenuItem $menu
     * @return Response
     * @throws Exception
     */
    public function newOpen(Request $request, MenuItem $menu): Response
    {
        $menu['quality_circle']->addChild('Neues offenes ToDo ', [
            'route' => 'quality_circle_todo_new_open'
        ]);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('quality_circle_home');
        }

        $toDo = new ToDoNew();
        $toDo->setSchool($this->getUser()->getCurrentSchool());
        $toDo->setCreatedBy($this->getUser());

        $form = $this->createForm(ToDoOpenType::class, $toDo);
        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($toDo);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('quality_circle_todo_edit', ['id' => $toDo->getId()]);
        }

        return $this->render('quality_circle/todo/new-open.html.twig', [
            'school' => $this->getUser()->getCurrentSchool(),
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @IsGranted("ROLE_MENSA_AG")
     * @param ToDoNew $toDo
     * @param MenuItem $menu
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     */
    public function edit(ToDoNew $toDo, MenuItem $menu, EntityManagerInterface $em): Response
    {
        if (\is_null($toDo)) {
            $this->getErrorMessage('ToDo nicht gefunden!');
            return $this->redirectToRoute('quality_circle_home');
        }
        if ($toDo->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        $menu['quality_circle']->addChild('ToDo ' . $toDo->getCreatedAt()->format('d.m.Y'), [
            'route' => 'quality_circle_todo_edit',
            'routeParameters' => ['id' => $toDo->getId()]
        ]);

        return $this->render('quality_circle/todo/edit.html.twig', [
            'todo' => $toDo,
            'school' => $this->getUser()->getCurrentSchool(),
            'ideaBoxIcons' => $em->getRepository(IdeaboxIcon::class)->getOrderedIdeaBoxIcons(),
            'flag_definitions' => $this->qualityCheckService->getFlagDefinitions()
        ]);
    }

    /**
     * @Route("/show/{id}", name="show")
     * @param ToDoNew  $toDo
     * @param MenuItem $menu
     * @return Response
     * @throws Exception
     */
    public function show(ToDoNew $toDo, MenuItem $menu): Response
    {
        if ($toDo->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        $menu['quality_circle']->addChild('ToDo vom ' . $toDo->getCreatedAt()->format('d.m.Y'), [
            'route' => 'quality_circle_todo_show',
            'routeParameters' => ['id' => $toDo->getId()]
        ]);

        return $this->render('quality_circle/todo/show.html.twig', [
            'school' => $this->getUser()->getCurrentSchool(),
            'todo' => $toDo,
            'flag_definitions' => $this->qualityCheckService->getFlagDefinitions()
        ]);
    }

    /**
     * @Route("/export/{id}", name="export")
     * @param ToDoNew $toDo
     * @return void
     * @throws Exception
     */
    public function export(ToDoNew $toDo): void
    {
        if ($toDo->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }
        // instantiate and use the dompdf class
        $dompdf = new Dompdf();
        $dompdf->loadHtml($this->render('pdf/quality_circle.html.twig', [
            'currentSchool' => $this->getUser()->getCurrentSchool(),
            'topic' => $this->getUser()->getCurrentSchool() . ": ",
            'todo' => $toDo
        ])->getContent());

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        $dompdf->stream();
    }

    /**
     * @Route("/complete/{id}", name="complete")
     * @IsGranted("ROLE_MENSA_AG")
     * @param ToDoNew $toDo
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws Exception
     */
    public function complete(ToDoNew $toDo, Request $request, MenuItem $menu): Response
    {
        if ($toDo->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }
        if (! $toDo->isClosable()) {
            $this->getErrorMessage("Das ToDo kann noch nicht geschlossen werden!");
            return $this->redirectToRoute('quality_circle_todo_edit', ['id' => $toDo->getId()]);
        }

        $menu['quality_circle']->addChild('ToDo ' . $toDo->getCreatedAt()->format('d.m.Y'), [
            'route' => 'quality_circle_todo_edit',
            'routeParameters' => ['id' => $toDo->getId()]
        ])->addChild('Abschließen', [
            'route' => 'quality_circle_todo_complete',
            'routeParameters' => ['id' => $toDo->getId()]
        ]);

        $form = $this->createForm(ToDoType::class, $toDo);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('quality_circle_todo_edit', ['id' => $toDo->getId()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $toDo->setCompleted($toDo->allActionPlansCompleted());
            $toDo->setClosed(true);
            $toDo->setClosedBy($this->getUser());
            $toDo->setClosedAt(new \DateTime());
            $this->getDoctrine()->getManager()->persist($toDo);
            $this->getDoctrine()->getManager()->flush();

            $this->getSuccessMessage('ToDo erfolgreich geschlossen!');

            return $this->redirectToRoute('quality_circle_home');
        }

        return $this->render('quality_circle/todo/complete.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/delete-todo/{id}", name="delete_todo")
     * @IsGranted("ROLE_FOOD_COMMISSIONER")
     * @IsGranted("ROLE_MENSA_AG")
     * @param ToDoNew $toDo
     * @return Response
     * @throws Exception
     */
    public function deleteToDo(ToDoNew $toDo): Response
    {
        if ($toDo->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        $this->getDoctrine()->getManager()->remove($toDo);
        $this->getDoctrine()->getManager()->flush();


        $this->getSuccessMessage('ToDo erfolgreich gelöscht!');

        return $this->redirectToRoute('quality_circle_home');
    }

    /**
     * @Route("/action-plan/{id}/{action_plan_id<\d+>?}", name="action_plan", defaults={"actionPlan"=null})
     * @ParamConverter("actionPlan", options={"mapping": {"action_plan_id": "id"}})
     * @IsGranted("ROLE_MENSA_AG")
     * @param ToDoNew $toDo
     * @param ActionPlanNew|null $actionPlan
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws Exception
     */
    public function actionPlan(ToDoNew $toDo, ?ActionPlanNew $actionPlan, Request $request, MenuItem $menu): Response
    {
        if ($toDo->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }
        if ($actionPlan && $actionPlan->isClosed()) {
            $this->getErrorMessage("Der Aktionsplan ist bereits geschlossen!");
            return $this->redirectToRoute('quality_circle_todo_edit', ['id' => $toDo->getId()]);
        }

        $menu['quality_circle']->addChild('ToDo ' . $toDo->getCreatedAt()->format('d.m.Y'), [
            'route' => 'quality_circle_todo_edit',
            'routeParameters' => ['id' => $toDo->getId()]
        ])->addChild('Aktionsplan ' . ($actionPlan ? 'bearbeiten' : 'anlegen'), [
            'route' => 'quality_circle_todo_action_plan',
            'routeParameters' => $actionPlan ? [
                'id' => $toDo->getId(),
                'action_plan_id' => $actionPlan->getId()
            ] : ['id' => $toDo->getId()]
        ]);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('quality_circle_todo_edit', ['id' => $toDo->getId()]);
        }

        if (! $actionPlan) {
            $actionPlan = new ActionPlanNew();
            $actionPlan->setToDo($toDo);
            $actionPlan->setCreatedBy($this->getUser());

            if ($request->get("idea")) {
                $actionPlan->setHow($request->get("idea"));
            }
        }

        $form = $this->createForm(ActionPlanType::class, $actionPlan);
        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($actionPlan);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('quality_circle_todo_edit', ['id' => $toDo->getId()]);
        }

        $ideaBoxIcons = $em->getRepository(IdeaboxIcon::class)->findAll();

        return $this->render('quality_circle/todo/action-plan.html.twig', [
            'actionPlan' => $actionPlan,
            'form' => $form->createView(),
            'school' => $this->getUser()->getCurrentSchool(),
            'ideaBoxIcons' => $ideaBoxIcons,
            'flag_definitions' => $this->qualityCheckService->getFlagDefinitions()
        ]);
    }

    /**
     * @Route("/action-plan/{id}/show", name="action_plan_show")
     * @param ToDoItem $toDoItem
     * @param Request $request
     * @param MenuItem $menu
     * @return Response
     * @throws Exception
     */
    public function actionPlanShow(ToDoItem $toDoItem, Request $request, MenuItem $menu): Response
    {
        if ($toDoItem->getTodo()->getResult()->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        $menu['quality_circle']->addChild('ToDo-Liste vom ' . $toDoItem->getTodo()->getCreatedAt()->format('d.m.Y'), [
            'route' => 'quality_circle_todo_edit'
        ])->addChild('Aktionsplan anzeigen', [
            'route' => 'quality_circle_todo_action_plan_show',
            'routeParameters' => ['id' => $toDoItem->getId()]
        ]);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('quality_circle_home');
        }

        if ($toDoItem->getActionPlan()) {
            $actionPlan = $toDoItem->getActionPlan();
        } else {
            $actionPlan = new ActionPlan();
            $actionPlan->setTodoItem($toDoItem);
            $actionPlan->setCreatedBy($this->getUser());
        }

        $r = \preg_replace(
            '/((http|ftp|https):\/\/)?([\w_-]+(?:(?:\.[\w_-]+)+))([\w.\,\@\?\^\=\%\&\:\/\~\+\#\-]*[\w\@\?\^\=\%\&\/\~\+\#\-])?/m',
            '<a href="${1}${3}${4}" target="_blank">${1}${3}${4}</a>',
            $toDoItem->getActionPlan()->getWhat()
        );
        $toDoItem->getActionPlan()->setWhat($r);

        return $this->render('quality_circle/todo/action-plan-show.html.twig', [
            'todoItem' => $toDoItem,
            'edit' => false,
        ]);
    }

    /**
     * @Route("/delete-action-plan/{id}", name="delete_action_plan")
     * @IsGranted("ROLE_MENSA_AG")
     * @param ActionPlanNew $actionPlan
     * @return Response
     * @throws Exception
     */
    public function deleteActionPlan(ActionPlanNew $actionPlan): Response
    {
        if ($actionPlan->getTodo()->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        $this->getDoctrine()->getManager()->remove($actionPlan);
        $this->getDoctrine()->getManager()->flush();

        $this->getSuccessMessage('Aktionsplan erfolgreich gelöscht!');

        return $this->redirectToRoute('quality_circle_todo_edit', ['id' => $actionPlan->getToDo()->getId()]);
    }

    /**
     * @Route("/complete-action-plan/{id}", name="complete_action_plan")
     * @IsGranted("ROLE_MENSA_AG")
     * @param ActionPlanNew $actionPlan
     * @param Request       $request
     * @param MenuItem      $menu
     * @return Response
     * @throws Exception
     */
    public function completeActionPlan(ActionPlanNew $actionPlan, Request $request, MenuItem $menu, EmailNotificationService $emailNotificationService): Response
    {
        if ($actionPlan->getTodo()->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        $menu['quality_circle']->addChild('ToDo vom ' . $actionPlan->getTodo()->getCreatedAt()->format('d.m.Y'), [
            'route' => 'quality_circle_todo_edit',
            'routeParameters' => ['id' => $actionPlan->getToDo()->getId()]
        ])->addChild($actionPlan->getWhat() . ': Abschließen', [
            'route' => 'quality_circle_todo_complete_action_plan',
            'routeParameters' => ['id' => $actionPlan->getId()]
        ]);

        $form = $this->createForm(ActionPlanCompleteType::class, $actionPlan);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('quality_circle_todo_edit', ['id' => $actionPlan->getToDo()->getId()]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $actionPlan->setClosed(true);
            $this->getDoctrine()->getManager()->persist($actionPlan);
            $this->getDoctrine()->getManager()->flush();

            $this->getSuccessMessage('Aktionsplan erfolgreich geschlossen!');

            $emailNotificationService->sendActionPlanMail($actionPlan);

            return $this->redirectToRoute('quality_circle_todo_edit', ['id' => $actionPlan->getToDo()->getId()]);
        }

        return $this->render('quality_circle/todo/action-plan-complete.html.twig', [
            'actionPlan' => $actionPlan,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/archive", name="archive")
     * @IsGranted("ROLE_MENSA_AG")
     * @return Response
     * @throws Exception
     */
    public function archive(): Response
    {
        /** @var ToDoRepository $tdr */
        $tdr = $this->getDoctrine()->getRepository(ToDo::class);
        $todo = $tdr->getUnarchivedToDoBySchool($this->getUser()->getCurrentSchool());

        if (\is_null($todo)) {
            $this->getErrorMessage('Keine aktuelle ToDo-Liste vorhanden!');

            return $this->redirectToRoute('quality_circle_home');
        }

        if (! $todo->allToDoClosed()) {
            $this->getErrorMessage('Es wurden noch nicht alle ToDo geschlossen!');

            return $this->redirectToRoute('quality_circle_todo_edit');
        }

        $todo->setArchived(true);
        $todo->setArchivedAt(new \DateTime());
        $todo->setArchivedBy($this->getUser());
        $this->getDoctrine()->getManager()->persist($todo);
        $this->getDoctrine()->getManager()->flush($todo);

        return $this->redirectToRoute('quality_circle_home');
    }
}
