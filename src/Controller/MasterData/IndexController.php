<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-28
 * Time: 10:20
 */

namespace App\Controller\MasterData;

use App\Controller\AbstractController;
use App\Form\SchoolType;
use App\Service\MasterDataService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/master_data", name="master_data_")
 * @IsGranted("ROLE_USER")
 */
class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @param MasterDataService $masterDataService
     * @return JsonResponse|Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function index(MasterDataService $masterDataService)
    {
        return $this->render('master_data/index/index.html.twig', [
            'hasFinalisedMasterData' => $masterDataService->hasFinalisedMasterData(),
            'school' => $this->getUser()->getCurrentSchool()
        ]);
    }

    /**
     * @Route("/export", name="export")
     * @param MasterDataService $masterDataService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|void
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function export(MasterDataService $masterDataService)
    {
        if (! $masterDataService->hasFinalisedMasterData()) {
            $this->getErrorMessage('Die Bearbeitung der Stammdaten ist noch nicht abgeschlossen');
            return $this->redirectToRoute('master_data_home');
        }

        // instantiate and use the dompdf class
        $options = new Options();
        $options->setChroot('/var/www/public');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->render('pdf/master_data.html.twig', [
            'currentSchool' => $this->getUser()->getCurrentSchool(),
            'topic' => $this->getUser()->getCurrentSchool() . ': Stammdaten',
            'data' => $masterDataService->getData()
        ])->getContent());
        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        $dompdf->stream();
    }

    /**
     * @Route("/export_blank", name="export_blank")
     * @param MasterDataService $masterDataService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|void
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function exportBlank(MasterDataService $masterDataService)
    {
        // instantiate and use the dompdf class
        $options = new Options();
        $options->setChroot('/var/www/public');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->render('pdf/master_data_blank.html.twig', [
            'currentSchool' => $this->getUser()->getCurrentSchool(),
            'topic' => $this->getUser()->getCurrentSchool() . ': Stammdaten',
            'data' => $masterDataService->getData(true)
        ])->getContent());
        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        $dompdf->stream();
    }

    /**
     * @Route("/edit-school", name="edit_school")
     * @Security("is_granted('ROLE_SCHOOL_AUTHORITIES')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws Exception
     */
    public function editSchool(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $school = $this->getUser()->getCurrentSchool();

        $form = $this->createForm(SchoolType::class, $school, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($school);
            $em->flush();

            $this->getSuccessMessage();

            return $this->redirectToRoute('master_data_home');
        }
        return $this->render('master_data/index/edit_school.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{step}", name="edit", defaults={"step":1})
     * @Security("is_granted('ROLE_FOOD_COMMISSIONER') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param int $step
     * @param Request $request
     * @param MasterDataService $masterDataService
     * @return Response
     * @throws Exception
     */
    public function edit(int $step, Request $request, MasterDataService $masterDataService): Response
    {
        $form = $masterDataService->getForm($step);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $masterDataService->save($step, $form->getData());
        }
        if ($request->request->has('next') && $form->isValid()) {
            return $this->redirectToRoute('master_data_edit', ['step' => $step + 1]);
        } elseif ($request->request->has('back')) {
            return $this->redirectToRoute('master_data_edit', ['step' => $step - 1]);
        } elseif ($request->request->has('finalise') && $form->isValid()) {
            try {
                $masterDataService->finalise();
                $this->getSuccessMessage('Fragebogen erfolgreich finalisiert!');
            } catch (\Throwable $e) {
                $this->getErrorMessage($e->getMessage());
                return $this->redirectToRoute('master_data_edit', ['step' => $step]);
            }
            return $this->redirectToRoute('master_data_home');
        }
        return $this->render($masterDataService->getTemplateByStep($step), [
            'form' => $form->createView(),
            'step' => $step,
            'readonly' => false,
            'categories' => $masterDataService->getCategories(),
            'max_steps' => $masterDataService->getMaxSteps()
        ]);
    }

    /**
     * @Route("/show/{step}", name="show", defaults={"step":1})
     * @param int $step
     * @param Request $request
     * @param MasterDataService $masterDataService
     * @return Response
     * @throws Exception
     */
    public function show(int $step, Request $request, MasterDataService $masterDataService): Response
    {
        if (! $masterDataService->hasFinalisedMasterData()) {
            $this->getErrorMessage('Die Bearbeitung der Stammdaten ist noch nicht abgeschlossen');
            return $this->redirectToRoute('master_data_home');
        }
        $form = $masterDataService->getForm($step, false, true);
        if ($request->request->has('next')) {
            return $this->redirectToRoute('master_data_show', ['step' => $step + 1]);
        } elseif ($request->request->has('back')) {
            return $this->redirectToRoute('master_data_show', ['step' => $step - 1]);
        } elseif ($request->request->has('close')) {
            return $this->redirectToRoute('master_data_home');
        }
        return $this->render($masterDataService->getTemplateByStep($step), [
            'form' => $form->createView(),
            'step' => $step,
            'readonly' => true,
            'categories' => $masterDataService->getCategories(),
            'max_steps' => $masterDataService->getMaxSteps()
        ]);
    }

    /**
     * @Route("/portrait", name="portrait")
     * @param MasterDataService $masterDataService
     * @return Response
     * @throws Exception
     */
    public function portrait(MasterDataService $masterDataService): Response
    {
        if (! $masterDataService->hasFinalisedMasterData()) {
            $this->getErrorMessage('Die Bearbeitung der Stammdaten ist noch nicht abgeschlossen');
            return $this->redirectToRoute('master_data_home');
        }
        $masterData = $masterDataService->getData();

        // instantiate and use the dompdf class
        $dompdf = new Dompdf();
        $dompdf->loadHtml($this->render('pdf/portrait.html.twig', [
            'currentSchool' => $this->getUser()->getCurrentSchool(),
            'topic' => $this->getUser()->getCurrentSchool(),
            'data' => $masterData
        ])->getContent());

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        $dompdf->stream();
    }
}
