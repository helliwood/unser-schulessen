<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-29
 * Time: 10:55
 */

namespace App\Controller\Survey;

use App\Controller\AbstractController;
use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveyQuestion;
use App\Entity\Survey\SurveyQuestionAnswer;
use App\Entity\Survey\SurveyQuestionChoice;
use App\Entity\Survey\SurveyQuestionChoiceAnswer;
use App\Entity\Survey\SurveyVoucher;
use App\Entity\User;
use App\Repository\Survey\SurveySchoolParticipationRepository;
use App\Repository\Survey\SurveyVoucherRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

#[Route(path: '/Umfrage', name: 'survey_public_')]
class PublicController extends AbstractController
{
    /**
     * @param Survey $survey
     * @param Request $request
     * @return Response
     * @throws \Doctrine\DBAL\ConnectionException
     */
    #[Route(path: '/{uuid}', name: 'show', requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function show(Survey $survey, Request $request): Response
    {
        if ($survey->isSchoolAuthoritySurvey() || $survey->isSchoolAuthorityTemplate()) {
            throw $this->createAccessDeniedException('Ungültige Umfrage.');
        }

        $form = $this->createFormBuilder()->create('survey', FormType::class)->getForm();
        for ($i = 1; $i <= $survey->getQuestions()->count(); $i++) {
            foreach ($survey->getQuestions() as $question) {
                if ($question->getOrder() === $i) {
                    if ($question->getType() === SurveyQuestion::TYPE_HAPPY_UNHAPPY && $question->getOrder() === $i) {
                        $form->add($question->getId(), ChoiceType::class, [
                            'label' => $question->getQuestion(),
                            'empty_data' => 'hierMussEinStringStehenSonstFunktioniertRequiredNicht',
                            'placeholder' => 'Eine Option wählen:',
                            'required' => false,
                            'multiple' => false,
                            'expanded' => true,
                            'constraints' => [new Type('bool')],
                            'choices' => [
                                'zufrieden' => true,
                                'nicht zufrieden' => false
                            ]
                        ]);
                    } elseif ($question->getType() === SurveyQuestion::TYPE_SINGLE) {
                        $form->add($question->getId(), ChoiceType::class, [
                            'label' => $question->getQuestion(),
                            'placeholder' => 'Eine Option wählen:',
                            'required' => false,
                            'multiple' => false,
                            'expanded' => true,
                            'choices' => $question->getChoices4Form()
                        ]);
                    } elseif ($question->getType() === SurveyQuestion::TYPE_MULTI) {
                        $form->add($question->getId(), ChoiceType::class, [
                            'label' => $question->getQuestion(),
                            'placeholder' => 'Eine Option wählen:',
                            'required' => false,
                            'multiple' => true,
                            'expanded' => true,
                            'choices' => $question->getChoices4Form()
                        ]);
                    } elseif ($question->getType() === SurveyQuestion::TYPE_TEXT) {
                        $form->add($question->getId(), TextareaType::class, [
                            'label' => $question->getQuestion(),
                            'help' => 'Bitte beantworten sie die Frage mit einem kleinen Text:',
                            'required' => false,
                            'constraints' => [
                                new Length([
                                    'max' => 250,
                                    'maxMessage' => 'Die Antwort darf maximal {{ limit }} Zeichen lang sein.'
                                ]),
                            ]
                        ]);
                    } else {
                        throw new \Exception('Type (' . $survey->getType() . ') not found!');
                    }
                }
            }
        }
        if ($survey->getType() === Survey::TYPE_VOUCHER) {
            $form->add('voucher', TextType::class, [
                'required' => true,
                'constraints' => [new NotBlank()],
            ]);
        }

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        /** @var SurveyVoucherRepository $svr */
        $svr = $em->getRepository(SurveyVoucher::class);
        if ($survey->getType() === Survey::TYPE_VOUCHER && $form->isSubmitted() && $form->getData()['voucher']) {
            $voucher = $svr->findByVoucher($form->getData()['voucher']);
            if (! $voucher) {
                $form->get('voucher')->addError(new FormError('Voucher nicht gefunden!'));
            } else {
                if ($svr->isVoucherInUse($voucher)) {
                    $form->get('voucher')->addError(new FormError('Voucher bereits benutzt!'));
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if (! \is_null($survey->getClosesAt()) && $survey->getClosesAt() < new \DateTime()) {
                $this->getErrorMessage('Die Befragung ist bereits geschlossen!');
                return $this->redirectToRoute('survey_public_show', ['uuid' => $survey->getUuid()]);
            }

            if ($survey->getState() !== Survey::STATE_ACTIVE) {
                $this->getErrorMessage('Die Befragung ist noch geschlossen!');
                return $this->redirectToRoute('survey_public_show', ['uuid' => $survey->getUuid()]);
            }

            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();
            $conn->beginTransaction();
            try {
                foreach ($form->getData() as $questionId => $answer) {
                    if (\is_numeric($questionId)) {
                        /** @var SurveyQuestion $question */
                        $question = $this->getDoctrine()->getManager()->find(SurveyQuestion::class, $questionId);
                        if (! $question || ! $survey->getQuestions()->contains($question)) {
                            throw new \Exception('Frage nicht gefunden!');
                        }

                        if ($question->getType() === SurveyQuestion::TYPE_HAPPY_UNHAPPY) {
                            $questionAnswer = new SurveyQuestionAnswer();
                            $questionAnswer->setQuestion($question);
                            $questionAnswer->setAnswer($answer);
                            $questionAnswer->setUserAgent($request->headers->get('User-Agent'));
                            $questionAnswer->setUserIp($request->getClientIp());
                            if ($survey->getType() === Survey::TYPE_VOUCHER) {
                                $questionAnswer->setVoucher($voucher);
                            }

                            if (\is_null($answer)) {
                                $question->setNotAnswered((int)$question->getNotAnswered() + 1);
                            } else {
                                $question->setAnswered($question->getAnswered() + 1);
                            }

                            $em->persist($questionAnswer);
                            $em->persist($question);
                        } elseif ($question->getType() === SurveyQuestion::TYPE_SINGLE ||
                            $question->getType() === SurveyQuestion::TYPE_MULTI) {
                            $choiceIds = \is_array($answer) ? $answer : [$answer];
                            if (! empty($answer)) {
                                foreach ($choiceIds as $choiceId) {
                                    /** @var SurveyQuestionChoice $choice */
                                    $choice = $this->getDoctrine()
                                        ->getManager()
                                        ->find(SurveyQuestionChoice::class, $choiceId);
                                    if (! $choice || ! $question->getChoices()->contains($choice)) {
                                        throw new \Exception('Antwort nicht gefunden!');
                                    }
                                    $questionChoiceAnswer = new SurveyQuestionChoiceAnswer();
                                    $questionChoiceAnswer->setQuestion($question);
                                    $questionChoiceAnswer->setChoice($choice);
                                    $questionChoiceAnswer->setUserAgent($request->headers->get('User-Agent'));
                                    $questionChoiceAnswer->setUserIp($request->getClientIp());
                                    if ($survey->getType() === Survey::TYPE_VOUCHER) {
                                        $questionChoiceAnswer->setVoucher($voucher);
                                    }
                                    $em->persist($questionChoiceAnswer);
                                }
                                $question->setAnswered($question->getAnswered() + 1);
                                $em->persist($question);
                            } else {
                                $questionChoiceAnswer = new SurveyQuestionChoiceAnswer();
                                $questionChoiceAnswer->setQuestion($question);
                                $questionChoiceAnswer->setChoice(null);
                                $questionChoiceAnswer->setUserAgent($request->headers->get('User-Agent'));
                                $questionChoiceAnswer->setUserIp($request->getClientIp());
                                $em->persist($questionChoiceAnswer);
                                $question->setNotAnswered((int)$question->getNotAnswered() + 1);
                                $em->persist($question);
                                $em->flush();
                            }
                        } elseif ($question->getType() === SurveyQuestion::TYPE_TEXT) {
                            if (! empty($answer)) {
                                $questionTextAnswer = new SurveyQuestionAnswer();
                                $questionTextAnswer->setQuestion($question);
                                $questionTextAnswer->setTextAnswer($answer);
                                $questionTextAnswer->setUserAgent($request->headers->get('User-Agent'));
                                $questionTextAnswer->setUserIp($request->getClientIp());
                                if ($survey->getType() === Survey::TYPE_VOUCHER) {
                                    $questionTextAnswer->setVoucher($voucher);
                                }

                                if (\is_null($answer)) {
                                    $question->setNotAnswered((int)$question->getNotAnswered() + 1);
                                } else {
                                    $question->setAnswered($question->getAnswered() + 1);
                                }

                                $em->persist($questionTextAnswer);
                                $em->persist($question);
                            }
                        } else {
                            throw new \Exception('Typ nicht gefunden!');
                        }
                    }
                }
                $survey->setNumberOfParticipants($survey->getNumberOfParticipants() + 1);
                $em->persist($survey);
                $em->flush();
                $conn->commit();

                $this->getSuccessMessage('Sie haben erfolgreich an der Umfrage teilgenommen.');
                return $this->redirectToRoute('survey_public_result', ['uuid' => $survey->getUuid()]);
            } catch (\Throwable $e) {
                $conn->rollBack();
            }
        }
        return $this->render('survey/public/show.html.twig', ['survey' => $survey,
            'form' => $form->createView(),
            'closed' => false /*$survey->getState() !== Survey::STATE_ACTIVE ||
                (! \is_null($survey->getClosesAt()) && $survey->getClosesAt() < new \DateTime())*/
        ]);
    }

    /**
     * @param Survey $survey
     * @param Request $request
     * @param SurveySchoolParticipationRepository $participationRepository
     * @return Response
     * @throws \Doctrine\DBAL\ConnectionException
     */
    #[Route(path: '/Traeger/{uuid}', name: 'authority_show', requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function authorityShow(
        Survey $survey,
        Request $request,
        SurveySchoolParticipationRepository $participationRepository
    ): Response {
        if (! $survey->isSchoolAuthoritySurvey()) {
            throw $this->createAccessDeniedException('Ungültige Umfrage.');
        }

        $currentUser = $this->getUser();
        $currentSchool = $currentUser ? $currentUser->getCurrentSchool() : null;
        if (! $currentSchool) {
            $this->getErrorMessage('Keine Schule ausgewählt.');
            return $this->redirectToRoute('home');
        }

        if (! $this->isGranted(User::ROLE_HEADMASTER) && ! $this->isGranted(User::ROLE_FOOD_COMMISSIONER)) {
            throw $this->createAccessDeniedException('Keine Berechtigung.');
        }

        $participation = $participationRepository->findParticipationBySchoolAndSurvey($currentSchool, $survey);
        if (! $participation || $participation->getHasParticipated()) {
            $this->getErrorMessage('Keine Berechtigung für diese Umfrage.');
            return $this->redirectToRoute('home');
        }

        if (! \is_null($survey->getClosesAt()) && $survey->getClosesAt() < new \DateTime()) {
            $this->getErrorMessage('Die Befragung ist bereits geschlossen!');
            return $this->redirectToRoute('home');
        }

        if ($survey->getState() !== Survey::STATE_ACTIVE) {
            $this->getErrorMessage('Die Befragung ist noch geschlossen!');
            return $this->redirectToRoute('home');
        }

        $form = $this->createFormBuilder()->create('survey', FormType::class)->getForm();
        for ($i = 1; $i <= $survey->getQuestions()->count(); $i++) {
            foreach ($survey->getQuestions() as $question) {
                if ($question->getOrder() === $i) {
                    if ($question->getType() === SurveyQuestion::TYPE_HAPPY_UNHAPPY && $question->getOrder() === $i) {
                        $form->add($question->getId(), ChoiceType::class, [
                            'label' => $question->getQuestion(),
                            'empty_data' => 'hierMussEinStringStehenSonstFunktioniertRequiredNicht',
                            'placeholder' => 'Eine Option wählen:',
                            'required' => false,
                            'multiple' => false,
                            'expanded' => true,
                            'constraints' => [new Type('bool')],
                            'choices' => [
                                'zufrieden' => true,
                                'nicht zufrieden' => false
                            ]
                        ]);
                    } elseif ($question->getType() === SurveyQuestion::TYPE_SINGLE) {
                        $form->add($question->getId(), ChoiceType::class, [
                            'label' => $question->getQuestion(),
                            'placeholder' => 'Eine Option wählen:',
                            'required' => false,
                            'multiple' => false,
                            'expanded' => true,
                            'choices' => $question->getChoices4Form()
                        ]);
                    } elseif ($question->getType() === SurveyQuestion::TYPE_MULTI) {
                        $form->add($question->getId(), ChoiceType::class, [
                            'label' => $question->getQuestion(),
                            'placeholder' => 'Eine Option wählen:',
                            'required' => false,
                            'multiple' => true,
                            'expanded' => true,
                            'choices' => $question->getChoices4Form()
                        ]);
                    } elseif ($question->getType() === SurveyQuestion::TYPE_TEXT) {
                        $form->add($question->getId(), TextareaType::class, [
                            'label' => $question->getQuestion(),
                            'help' => 'Bitte beantworten sie die Frage mit einem kleinen Text:',
                            'required' => false,
                            'constraints' => [
                                new Length([
                                    'max' => 250,
                                    'maxMessage' => 'Die Antwort darf maximal {{ limit }} Zeichen lang sein.'
                                ]),
                            ]
                        ]);
                    } else {
                        throw new \Exception('Type (' . $survey->getType() . ') not found!');
                    }
                }
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();
            $conn->beginTransaction();
            try {
                foreach ($form->getData() as $questionId => $answer) {
                    if (\is_numeric($questionId)) {
                        /** @var SurveyQuestion $question */
                        $question = $this->getDoctrine()->getManager()->find(SurveyQuestion::class, $questionId);
                        if (! $question || ! $survey->getQuestions()->contains($question)) {
                            throw new \Exception('Frage nicht gefunden!');
                        }

                        if ($question->getType() === SurveyQuestion::TYPE_HAPPY_UNHAPPY) {
                            $questionAnswer = new SurveyQuestionAnswer();
                            $questionAnswer->setQuestion($question);
                            $questionAnswer->setAnswer($answer);
                            $questionAnswer->setUserAgent($request->headers->get('User-Agent'));
                            $questionAnswer->setUserIp($request->getClientIp());

                            if (\is_null($answer)) {
                                $question->setNotAnswered((int)$question->getNotAnswered() + 1);
                            } else {
                                $question->setAnswered($question->getAnswered() + 1);
                            }

                            $this->getDoctrine()->getManager()->persist($questionAnswer);
                            $this->getDoctrine()->getManager()->persist($question);
                        } elseif ($question->getType() === SurveyQuestion::TYPE_SINGLE ||
                            $question->getType() === SurveyQuestion::TYPE_MULTI) {
                            $choiceIds = \is_array($answer) ? $answer : [$answer];
                            if (! empty($answer)) {
                                foreach ($choiceIds as $choiceId) {
                                    /** @var SurveyQuestionChoice $choice */
                                    $choice = $this->getDoctrine()
                                        ->getManager()
                                        ->find(SurveyQuestionChoice::class, $choiceId);
                                    if (! $choice || ! $question->getChoices()->contains($choice)) {
                                        throw new \Exception('Antwort nicht gefunden!');
                                    }
                                    $questionChoiceAnswer = new SurveyQuestionChoiceAnswer();
                                    $questionChoiceAnswer->setQuestion($question);
                                    $questionChoiceAnswer->setChoice($choice);
                                    $questionChoiceAnswer->setUserAgent($request->headers->get('User-Agent'));
                                    $questionChoiceAnswer->setUserIp($request->getClientIp());
                                    $this->getDoctrine()->getManager()->persist($questionChoiceAnswer);
                                }
                                $question->setAnswered($question->getAnswered() + 1);
                                $this->getDoctrine()->getManager()->persist($question);
                            } else {
                                $questionChoiceAnswer = new SurveyQuestionChoiceAnswer();
                                $questionChoiceAnswer->setQuestion($question);
                                $questionChoiceAnswer->setChoice(null);
                                $questionChoiceAnswer->setUserAgent($request->headers->get('User-Agent'));
                                $questionChoiceAnswer->setUserIp($request->getClientIp());
                                $this->getDoctrine()->getManager()->persist($questionChoiceAnswer);
                                $question->setNotAnswered((int)$question->getNotAnswered() + 1);
                                $this->getDoctrine()->getManager()->persist($question);
                                $this->getDoctrine()->getManager()->flush();
                            }
                        } elseif ($question->getType() === SurveyQuestion::TYPE_TEXT) {
                            if (! empty($answer)) {
                                $questionTextAnswer = new SurveyQuestionAnswer();
                                $questionTextAnswer->setQuestion($question);
                                $questionTextAnswer->setTextAnswer($answer);
                                $questionTextAnswer->setUserAgent($request->headers->get('User-Agent'));
                                $questionTextAnswer->setUserIp($request->getClientIp());

                                if (\is_null($answer)) {
                                    $question->setNotAnswered((int)$question->getNotAnswered() + 1);
                                } else {
                                    $question->setAnswered($question->getAnswered() + 1);
                                }

                                $this->getDoctrine()->getManager()->persist($questionTextAnswer);
                                $this->getDoctrine()->getManager()->persist($question);
                            }
                        } else {
                            throw new \Exception('Typ nicht gefunden!');
                        }
                    }
                }
                $survey->setNumberOfParticipants($survey->getNumberOfParticipants() + 1);
                $participation->setHasParticipated(true);
                $participation->setParticipatedAt(new \DateTime());
                $this->getDoctrine()->getManager()->persist($survey);
                $this->getDoctrine()->getManager()->persist($participation);
                $this->getDoctrine()->getManager()->flush();
                $conn->commit();

                $this->getSuccessMessage('Sie haben erfolgreich an der Umfrage teilgenommen.');
                return $this->redirectToRoute('survey_public_result', ['uuid' => $survey->getUuid()]);
            } catch (\Throwable $e) {
                $conn->rollBack();
            }
        }

        return $this->render('survey/public/authority_show.html.twig', [
            'survey' => $survey,
            'form' => $form->createView(),
            'closed' => false,
        ]);
    }

    /**
     * @param Survey $survey
     * @return Response
     */
    #[Route(path: '/Qr/{uuid}', name: 'qr', requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function qr(Survey $survey): Response
    {
        return $this->render('survey/public/qr.html.twig', [
            'survey' => $survey,
        ]);
    }

    /**
     * @param Survey $survey
     * @return Response
     */
    #[Route(path: '/result/{uuid}', name: 'result', requirements: ['uuid' => '[0-9a-fA-F-]{36}'])]
    public function result(
        Survey $survey,
        SurveySchoolParticipationRepository $participationRepository
    ): Response {
        if ($survey->isSchoolAuthoritySurvey()) {
            $currentUser = $this->getUser();
            $currentSchool = $currentUser ? $currentUser->getCurrentSchool() : null;
            if (! $currentSchool) {
                $this->getErrorMessage('Keine Schule ausgewählt.');
                return $this->redirectToRoute('home');
            }

            $participation = $participationRepository->findParticipationBySchoolAndSurvey($currentSchool, $survey);
            if (! $participation || ! $participation->getHasParticipated()) {
                $this->getErrorMessage('Keine Berechtigung für die Auswertung.');
                return $this->redirectToRoute('home');
            }

            return $this->render('survey/public/authority_result.html.twig', [
                'survey' => $survey,
            ]);
        }

        return $this->render('survey/public/result.html.twig', [
            'survey' => $survey,
        ]);
    }
}
