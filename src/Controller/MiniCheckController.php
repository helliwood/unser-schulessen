<?php

namespace App\Controller;

use App\Entity\QualityCheck\MiniCheckAnswer;
use App\Entity\QualityCheck\MiniCheckResult;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use App\Entity\School;
use App\Form\MiniCheckContactDataType;
use App\Form\MiniCheckSchoolType;
use App\Service\MiniCheckService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Imagick;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/Mini-Check", name="minicheck_")
 */
class MiniCheckController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function indexAction(SessionInterface $session, Request $request, EntityManagerInterface $entityManager, MiniCheckService $miniCheckService): Response
    {
        $submitted = $request->isMethod('POST');
        if (! $miniCheckService->isMiniCheckAvailable()) {
            return $this->render('mini-check/disabled.html.twig');
        }

        $school = $session->get('minicheck_school', new School());
        if (! $school->getSchoolNumber() && $school instanceof School) {
            $school->setSchoolNumber("MC-" . \bin2hex(\random_bytes(3)));
            $school->setAuditEnd(new \DateTime());
            $school->getAuditEnd()->add(new \DateInterval('P1Y'));
        }
        $result = $session->get('minicheck_result');
        if ($result && ! \is_null($result->getSchool()->getId())) {
            $school = $entityManager->find(School::class, $result->getSchool()->getId());
        }

        $form = $this->createForm(MiniCheckSchoolType::class, $school, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $school->setMiniCheck(true);
            if (! \is_null($school->getId())) {
                $entityManager->persist($school);
                $entityManager->flush();
            }
            $session->set('minicheck_school', $school);
            return $this->redirectToRoute('minicheck_step2');
        }
        return $this->render('mini-check/index.html.twig', [
            'form' => $form->createView(),
            'step' => 1,
            'doNotScroll' => ! $submitted,
        ]);
    }

    /**
     * @Route("/step2", name="step2")
     * @throws NonUniqueResultException
     */
    public function step2Action(SessionInterface $session, Request $request, MiniCheckService $miniCheckService, EntityManagerInterface $entityManager): Response
    {
        if (! $miniCheckService->isMiniCheckAvailable()) {
            return $this->render('mini-check/disabled.html.twig');
        }

        $school = $session->get('minicheck_school');
        if (! $school) {
            return $this->redirectToRoute('minicheck_home');
        }

        $formData = [];
        $result = $session->get('minicheck_result');
        if (! $result) {
            $result = $miniCheckService->createMiniCheckResult();
            $result->setSchool($school);
            $session->set('minicheck_result', $result);
        } else {
            foreach ($result->getAnswers() as $answer) {
                $formData[$answer->getQuestion()->getId()] = $answer->getAnswer();
            }
        }

        $form = $miniCheckService->getForm($result, $formData);
        $form->setData($formData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (! \is_null($result->getId())) {
                $result = $entityManager->find(MiniCheckResult::class, $result->getId());
            }
            $result->setQuestionnaire($entityManager->find(Questionnaire::class, $result->getQuestionnaire()->getId()));
            $result->setAnswers(new ArrayCollection([]));
            foreach ($form->getData() as $questionId => $answer) {
                $answerObj = new MiniCheckAnswer();
                $answerObj->setQuestion($entityManager->find(Question::class, $questionId));
                $answerObj->setAnswer($answer);
                $result->addAnswer($answerObj);
            }
            $entityManager->persist($result);
            $entityManager->flush();
            $session->set('minicheck_result', $result);
            return $this->redirectToRoute('minicheck_summary');
        }

        return $this->render('mini-check/step2.html.twig', [
            'form' => $form->createView(),
            'school' => $school
        ]);
    }

    /**
     * @Route("/summary", name="summary")
     */
    public function summaryAction(SessionInterface $session, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $result = $session->get('minicheck_result');
        if (! $result instanceof MiniCheckResult) {
            return $this->redirectToRoute('minicheck_home');
        }
        $result = $entityManager->find(MiniCheckResult::class, $result->getId());
        $complete = false;
        $stats = $result->getStats();
        $form = $this->createForm(MiniCheckContactDataType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result->getSchool()->setMiniCheckName($form->get('name')->getData());
            $result->getSchool()->setMiniCheckEmail($form->get('email')->getData());
            $entityManager->persist($result->getSchool());
            $entityManager->flush();

            // PDF erstellen
            $dompdf = new Dompdf();
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $dompdf->setOptions($options);

            // Gauge-Grafik generieren
            $svg = $this->chart($result)->getContent();
            $im = new Imagick();
            $im->readImageBlob($svg);
            $im->resizeImage(1000, 500, Imagick::FILTER_CATROM, 1, true);
            $im->setImageFormat("png24");
            $img = \base64_encode($im->__toString());

            $dompdf->loadHtml($this->render('mini-check/pdf/summary.html.twig', [
                'result' => $result,
                'stats' => $stats,
                'currentSchool' => $result->getSchool(),
                'topic' => 'Auswertung Mini-Check vom ' . $result->getCreatedAt()->format('d.m.Y'),
                'img' => $img
            ])->getContent());

            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdf = $dompdf->output();
            /*
            $response = new Response($pdf);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="Mini-Check-vom-' . \date("Y-m-d") . '.pdf"');
            return $response;*/
            // E-Mail erstellen und versenden
            $email = (new TemplatedEmail())
                ->from(new Address('bb@unser-schulessen.de', 'Unser Schulessen'))
                ->bcc(
                    new Address('info@unser-schulessen.de', 'Unser Schulessen'),
                    new Address($translator->trans("contact_email"), $translator->trans("contact_name"))
                )->to($result->getSchool()->getMiniCheckEmail())
                ->subject('Ihre Mini-Check Auswertung')
                ->htmlTemplate('emails/mini-check.html.twig')
                ->context(
                    [
                        'result' => $result
                    ]
                )
                ->attach($pdf, "Mini-Check-vom-" . \date("Y-m-d") . ".pdf", 'application/pdf');

            $mailer->send($email);

            $session->remove('minicheck_result');
            $session->remove('minicheck_school');
            $complete = true;
        }

        return $this->render('mini-check/summary.html.twig', [
            'result' => $result,
            'stats' => $stats,
            'form' => $form->createView(),
            'complete' => $complete,
            'gauge' => $this->chart($result)->getContent()
        ]);
    }

    /**
     * @Route("/chart/{result}", name="chart")
     * @param MiniCheckResult $result
     * @return Response
     * @throws Exception
     */
    public function chart(MiniCheckResult $result): Response
    {
        $stats = $result->getGaugeStats();
        $total = \count($result->getAnswers());

        $svg = '';
        $currentPercent = 0;
        if ($stats["true"] > 0) {
            $percent = ($stats["true"] / $total * 100) / 2; //halbkreis
            $svg .= '<circle class="donut-segment" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#04B100" stroke-width="10" stroke-dasharray="' . $percent . ' ' . (100 - $percent) . '" stroke-dashoffset="' . (50 - $currentPercent) . '"></circle>';
            $currentPercent += $percent;
        }
        if ($stats["partial"] > 0) {
            $percent = ($stats["partial"] / $total * 100) / 2; //halbkreis
            $svg .= '<circle class="donut-segment" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#FFA700" stroke-width="10" stroke-dasharray="' . $percent . ' ' . (100 - $percent) . '" stroke-dashoffset="' . (50 - $currentPercent) . '"></circle>';
            $currentPercent += $percent;
        }
        if ($stats["false"] > 0) {
            $percent = ($stats["false"] / $total * 100) / 2; //halbkreis
            $svg .= '<circle class="donut-segment" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#D30000" stroke-width="10" stroke-dasharray="' . $percent . ' ' . (100 - $percent) . '" stroke-dashoffset="' . (50 - $currentPercent) . '"></circle>';
            $currentPercent += $percent;
        }
        if ($stats["not_answered"] > 0) {
            $percent = ($stats["not_answered"] / $total * 100) / 2; //halbkreis
            $svg .= '<circle class="donut-segment" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#9E9E9E" stroke-width="10" stroke-dasharray="' . $percent . ' ' . (100 - $percent) . '" stroke-dashoffset="' . (50 - $currentPercent) . '"></circle>';
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
}
