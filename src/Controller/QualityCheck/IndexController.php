<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-28
 * Time: 15:20
 */

namespace App\Controller\QualityCheck;

use App\Controller\AbstractController;
use App\Entity\QualityCheck\Result;
use App\EventSubscriber\BeforeControllerInterface;
use App\Repository\QualityCheck\ResultRepository;
use App\Service\EmailNotificationService;
use App\Service\MasterDataService;
use App\Service\QualityCheckService;
use Doctrine\ORM\NonUniqueResultException;
use Dompdf\Dompdf;
use Exception;
use Imagick;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/quality_check", name="quality_check_")
 * @IsGranted("ROLE_USER")
 */
class IndexController extends AbstractController implements BeforeControllerInterface
{
    /**
     * @var MasterDataService $masterDataService
     */
    protected $masterDataService;

    /**
     * @var EmailNotificationService $emailNotificationService
     */
    protected EmailNotificationService $emailNotificationService;


    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * IndexController constructor.
     * @param MailerInterface $mailer
     * @param MasterDataService $masterDataService
     * @param EmailNotificationService $emailNotificationService
     * @param SessionInterface $session
     * @param ParameterBagInterface $params
     */
    public function __construct(
        MailerInterface $mailer,
        MasterDataService $masterDataService,
        EmailNotificationService $emailNotificationService,
        SessionInterface $session,
        ParameterBagInterface $params
    ) {
        parent::__construct($mailer, $params);
        $this->masterDataService = $masterDataService;
        $this->emailNotificationService = $emailNotificationService;
        $this->session = $session;
    }

    /**
     * @param ControllerEvent $event
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function before(ControllerEvent $event): void
    {
        if ($this->getUser() && ! $this->masterDataService->hasFinalisedMasterData()) {
            $this->getErrorMessage('Sie müssen erst die Stammdaten ausfüllen.');

            $event->setController(function () {
                return $this->redirectToRoute('home');
            });
        }

        return;
    }

    /**
     * @Route("/", name="home")
     * @param Request $request
     * @param QualityCheckService $qualityCheckService
     * @param array|null $flags
     * @return Response|JsonResponse
     * @throws NonUniqueResultException
     */
    public function index(Request $request, QualityCheckService $qualityCheckService): Response
    {
        if ($request->isXmlHttpRequest()) {
            /** @var ResultRepository $rr */
            $rr = $this->getDoctrine()->getRepository(Result::class);
            return new JsonResponse($rr->find4Ajax(
                $this->getUser()->getCurrentSchool(),
                $request->query->get('sort', 'finalisedAt'),
                $request->query->getBoolean('sortDesc', true),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        $result = $qualityCheckService->getResult();

        return $this->render('quality_check/index/index.html.twig', [
            'result' => $result,
            'active_flags' => [],
            'hasUnfinalised' => $qualityCheckService->hasUnfinalisedQualityCheck(),
            'school' => $this->getUser()->getCurrentSchool(),
            'flag_icons' => $qualityCheckService->getFlagIcons(),
            'flag_definitions' => $qualityCheckService->getFlagDefinitions(),
        ]);
    }

    /**
     * @Route("/edit/{step}", name="edit", defaults={"step":1})
     * @IsGranted("ROLE_FOOD_COMMISSIONER")
     * @param int $step
     * @param QualityCheckService $qualityCheckService
     * @param MenuItem $menu
     * @param Request $request
     * @param EmailNotificationService $emailNotificationService
     * @return Response
     * @throws Exception
     */
    public function edit(int $step, QualityCheckService $qualityCheckService, MenuItem $menu, Request $request, EmailNotificationService $emailNotificationService): Response
    {
        $menu['quality_check']->addChild('Bearbeiten', [
            'route' => 'quality_check_edit'
        ]);
        $stepsTotal = $qualityCheckService->getStepsTotal();
        if ($step < 1) {
            $step = 1;
        }
        if ($step > $stepsTotal) {
            $step = $stepsTotal;
        }
        $form = $qualityCheckService->getForm($step);
        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $qualityCheckService->save($step, $form->getData(), $request->request->get('clear_category', []));
            }

            if ($request->request->has('next') && ! $request->request->get('Questionnaire')['stepPointer'] && $form->isValid()) {
                return $this->redirectToRoute('quality_check_edit', ['step' => $step + 1]);
            } elseif ($request->request->has('next') && $request->request->get('Questionnaire')['stepPointer'] && $form->isValid()) {
                return $this->redirectToRoute('quality_check_edit', ['step' => $request->request->get('Questionnaire')['stepPointer']]);
            } elseif ($request->request->has('back')) {
                return $this->redirectToRoute('quality_check_edit', ['step' => $step - 1]);
            } elseif ($request->request->has('save') && $form->isValid()) {
                return $this->redirectToRoute('quality_check_home');
            } elseif ($request->request->has('finalise') && $form->isValid()) {
                $result = $qualityCheckService->finalise();

                $emailNotificationService->sendQualityCheckResultMail($result);

                $this->getSuccessMessage('Der Qualitäts-Check wurde erfolgreich finalisiert.');
                return $this->redirectToRoute('quality_check_home');
            }

            $answeredCategories = [];
            foreach ($qualityCheckService->getUnfinalisedResult()->getAnsweredCategories() as $answeredCategory) {
                $answeredCategories[] = $answeredCategory->getOrder();
            }

            return $this->render('quality_check/index/edit.html.twig', [
                'step' => $step,
                'last_result' => $qualityCheckService->getLastResult(),
                'steps_total' => $stepsTotal,
                'category' => $qualityCheckService->getCategory($step),
                'form' => $form->createView(),
                'result' => $qualityCheckService->getUnfinalisedResult(),
                'onlyFormula' => $qualityCheckService->hasOnlyFormulaQuestions($step),
                'hideModal' => $this->session->get('hideModal', false),
                'answeredCategories' => $answeredCategories,
                'flag_definitions' => $qualityCheckService->getFlagDefinitions(),
            ]);
        } catch (\Throwable $e) {
            $this->getErrorMessage($e->getMessage());
            return $this->redirectToRoute('quality_check_home');
        }
    }

    /**
     * @Route("/check/{question_id}/{value}", name="check")
     * @IsGranted("ROLE_FOOD_COMMISSIONER")
     * @param int $question_id
     * @param int $value
     * @param QualityCheckService $qualityCheckService
     * @return Response
     * @throws \Exception
     */
    public function check(int $question_id, int $value, QualityCheckService $qualityCheckService): Response
    {
        return new JsonResponse($qualityCheckService->calculateAnswer($question_id, $value));
    }

    /**
     * @Route("/skip/{step}", name="skip")
     * @IsGranted("ROLE_FOOD_COMMISSIONER")
     * @param int $step
     * @param QualityCheckService $qualityCheckService
     * @param Request $request
     * @return Response
     */
    public function skip(int $step, QualityCheckService $qualityCheckService, Request $request): Response
    {
        if ($request->query->has('hideModal')) {
            $this->session->set('hideModal', (bool)$request->query->get('hideModal'));
        }
        $qualityCheckService->skip($step);
        return $this->redirectToRoute('quality_check_edit', ['step' => $step + 1]);
    }

    /**
     * @Route("/result/{id}", name="result", defaults={"id":null})
     * @param int|null $id
     * @param QualityCheckService $qualityCheckService
     * @param MenuItem $menu
     * @param Request $request
     * @return Response
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function result(?int $id, QualityCheckService $qualityCheckService, MenuItem $menu, Request $request): Response
    {
        $active_flags = $request->query->get('flags', []);

        /** @var Result $result */
        $result = $qualityCheckService->getResult($id, $active_flags);

        if (! $result) {
            $this->addFlash('danger', 'Ergebnis nicht gefunden!');
            return $this->redirectToRoute('quality_check_home');
        }

        if ($result->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        $menu['quality_check']->addChild('Ergebnis vom ' . $result->getFinalisedAt()->format('d.m.Y'), [
            'route' => 'quality_check_result',
            'routeParameters' => $id ? ['id' => $id] : []
        ]);
//        dd($result->getAnsweredCategories());

        return $this->render('quality_check/index/result.html.twig', [
            'school' => $this->getUser()->getCurrentSchool(),
            'result' => $result,
            'questionnaire' => $result->getQuestionnaire(),
            'blanco' => false,
            'active_flags' => $active_flags,
            'flag_definitions' => $qualityCheckService->getFlagDefinitions(),
        ]);
    }

    /**
     * @Route("/export/{id}", name="export")
     * @param int $id
     * @param QualityCheckService $qualityCheckService
     * @throws \Exception
     */
    public function export(int $id, QualityCheckService $qualityCheckService): void
    {
        $result = $qualityCheckService->getResult($id);
        if ($result->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }
        $svg = $this->chart($result, $qualityCheckService)->getContent();
        $im = new Imagick();
        $im->readImageBlob($svg);
        $im->resizeImage(1000, 500, Imagick::FILTER_CATROM, 1, true);
        $im->setImageFormat("png24");
        $img = \base64_encode($im->__toString());
        $svg = $this->chart($result, $qualityCheckService)->getContent();
        $im = new Imagick();
        $im->readImageBlob($svg);
        $im->resizeImage(1000, 500, Imagick::FILTER_CATROM, 1, true);
        $im->setImageFormat("png24");
        $img_sustainable = \base64_encode($im->__toString());
        // instantiate and use the dompdf class
        $dompdf = new Dompdf();
        $content = $this->render('pdf/quality_check.html.twig', [
            'currentSchool' => $this->getUser()->getCurrentSchool(),
            'topic' => $this->getUser()->getCurrentSchool() . ': ',
            'result' => $result,
            'qualityCheckService' => $qualityCheckService,
            'img' => $img,
            'img_sustainable' => $img_sustainable
        ])->getContent();
        $dompdf->loadHtml($content);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        $dompdf->stream();
    }

    /**
     * @Route("/chart/{result}/{active_flags}", name="chart")
     * @param Result $result
     * @param QualityCheckService $qualityCheckService
     * @param string|null $active_flags
     * @return Response
     * @throws NonUniqueResultException
     */
    public function chart(Result $result, QualityCheckService $qualityCheckService, ?string $active_flags = null): Response
    {
        // Flag-Parameter verarbeiten
        $active_flags = \json_decode($active_flags, true);
        $result = $qualityCheckService->getResult($result->getId(), $active_flags);
        $countTrue = $countPartial = $countFalse = 0;
        foreach ($result->getAnsweredCategories($active_flags) as $answeredCategory) {
            $countTrue += $answeredCategory->countAnswers($result, Result::ANSWER_TRUE);
            $countPartial += $answeredCategory->countAnswers($result, Result::ANSWER_PARTIAL);
            $countFalse += $answeredCategory->countAnswers($result, Result::ANSWER_FALSE);
        }

        $total = \count($result->getQuestions($active_flags));

        $svg = '';
        $currentPercent = 0;
        if ($countTrue > 0) {
            $percent = ($countTrue / $total * 100) / 2; //halbkreis
            $svg .= '<circle class="donut-segment" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#04B100" stroke-width="10" stroke-dasharray="' . $percent . ' ' . (100 - $percent) . '" stroke-dashoffset="' . (50 - $currentPercent) . '"></circle>';
            $currentPercent += $percent;
        }
        if ($countPartial > 0) {
            $percent = ($countPartial / $total * 100) / 2; //halbkreis
            $svg .= '<circle class="donut-segment" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#FFA700" stroke-width="10" stroke-dasharray="' . $percent . ' ' . (100 - $percent) . '" stroke-dashoffset="' . (50 - $currentPercent) . '"></circle>';
            $currentPercent += $percent;
        }
        if ($countFalse > 0) {
            $percent = ($countFalse / $total * 100) / 2; //halbkreis
            $svg .= '<circle class="donut-segment" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#D30000" stroke-width="10" stroke-dasharray="' . $percent . ' ' . (100 - $percent) . '" stroke-dashoffset="' . (50 - $currentPercent) . '"></circle>';
        }
        $linesSvg = '';
        if ($total > 0) {
            for ($i = 0; $i <= $total; $i++) {
                $linesSvg .= '<line x1="25" y1="4" x2="25" y2="6" stroke-width="0.25" stroke="#DDD" transform="rotate(' . (180 / $total * $i) . ' 25 25)"></line>';
            }
        }
        return new Response('<?xml version="1.0" encoding="UTF-8" standalone="no"?> 
        <svg viewBox="0 0 50 25" width="1000" height="500" xmlns="http://www.w3.org/2000/svg" xmlns:xlink= "http://www.w3.org/1999/xlink">
            <circle class="donut-ring" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#EBEBEB" stroke-width="10"></circle>
            ' . $svg . '
            <g transform="rotate(-90 25 25)">
            ' . $linesSvg . '
            </g>
        </svg>', 200, ['Content-Type' => 'image/svg+xml']);
    }

    /**
     * @Route("/copy", name="copy")
     * @param QualityCheckService $qualityCheckService
     * @return Response
     * @throws \Exception
     */
    public function copy(QualityCheckService $qualityCheckService): Response
    {
        $qualityCheckService->copyLastResult();

        return $this->redirectToRoute('quality_check_home');
    }


    /**
     * @Route("/blanco", name="blanco")
     * @param QualityCheckService $qualityCheckService
     * @param MenuItem $menu
     * @return Response
     * @throws \Exception
     */
    public function blancoResult(QualityCheckService $qualityCheckService, MenuItem $menu): Response
    {
        $menu['quality_check']->addChild('Blanko QC', [
            'route' => 'quality_check_blanco',
        ]);

        $result = $qualityCheckService->getUnfinalisedResult();
        $questionnaire = $result->getQuestionnaire();

        return $this->render('quality_check/index/blanco.html.twig', [
            'questionnaire' => $questionnaire,
            'flag_definitions' => $qualityCheckService->getFlagDefinitions()
        ]);
    }
}
