<?php

namespace App\Controller\SchoolAuthority;

use App\Entity\School;
use App\Entity\SchoolAuthority;
use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveyQuestion;
use App\Entity\Survey\SurveyQuestionAnswer;
use App\Entity\Survey\SurveyQuestionChoiceAnswer;
use App\Entity\Survey\SurveySchoolParticipation;
use App\Form\Survey\SchoolAuthoritySurveyType;
use App\Repository\Survey\SurveyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;

#[Route(path: '/schultraeger')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_SCHOOL_AUTHORITY')]
class SurveyController extends AbstractController
{
    #[Route(path: '/umfragen', name: 'school_authority_surveys')]
    public function surveys(\Knp\Menu\MenuItem $menu): Response
    {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();

        $menu['school_authority_surveys']->addChild('Meine Umfragen', [
            'route' => 'school_authority_surveys',
        ]);

        return $this->render('school_authority/survey/surveys.html.twig', [
            'schoolAuthority' => $schoolAuthority,
            'canManageSurveys' => true,
        ]);
    }

    #[Route(path: '/umfragen/ajax', name: 'school_authority_surveys_ajax')]
    public function surveysAjax(Request $request, SurveyRepository $surveyRepository, EntityManagerInterface $em): JsonResponse
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            if ($request->get('action') === 'delete_survey') {
                $survey = $em->getRepository(Survey::class)->find($request->get('survey_id', null));
                $schoolAuthority = $this->getUser()->getSchoolAuthority();
                if ($survey instanceof Survey
                    && $survey->getSchoolAuthority() === $schoolAuthority
                    && $survey->getState() === Survey::STATE_NOT_ACTIVATED
                ) {
                    $em->remove($survey);
                    $em->flush();
                }
            }
        }

        $schoolAuthority = $this->getUser()->getSchoolAuthority();

        $search = \trim((string) $request->query->get('search', ''));
        $sort = $request->query->get('sort', 'createdAt');
        $sortDesc = $request->query->getBoolean('sortDesc', true);
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('size', $request->query->getInt('limit', 10));

        $result = $surveyRepository->findBySchoolAuthority4Ajax(
            $schoolAuthority,
            $sort,
            $sortDesc,
            $page,
            $limit,
            $search
        );

        return new JsonResponse($result);
    }

    #[Route(path: '/umfragen/new', name: 'school_authority_survey_new')]
    public function newSelect(\Knp\Menu\MenuItem $menu): Response
    {
        $menu['school_authority_surveys']->addChild('Neue Umfrage', [
            'route' => 'school_authority_survey_new',
        ]);

        return $this->render('school_authority/survey/new_select.html.twig');
    }

    #[Route(path: '/umfragen/new/{kind}', name: 'school_authority_survey_new_form', requirements: ['kind' => 'survey|template'])]
    public function new(Request $request, EntityManagerInterface $em, \Knp\Menu\MenuItem $menu, string $kind): Response
    {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();

        $menu['school_authority_surveys']->addChild('Neue Umfrage', [
            'route' => 'school_authority_survey_new_form',
            'routeParameters' => ['kind' => $kind],
        ]);

        $survey = new Survey();
        $survey->setSchoolAuthority($schoolAuthority);
        $survey->setType(Survey::TYPE_OPEN);
        $survey->setCreatedBy($this->getUser());
        $survey->setSurveyTemplate($kind === 'template');

        $form = $this->createForm(SchoolAuthoritySurveyType::class, $survey);
        $form->handleRequest($request);
        $schools = $schoolAuthority ? $schoolAuthority->getSchools() : [];
        $selectedSchoolIds = (array) $request->request->all('schools');

        if ($form->isSubmitted() && $form->isValid()) {
            if ($kind === 'template') {
                $survey->setSurveyTemplate(true);
                $survey->setClosesAt(null);
            } else {
                $survey->setSurveyTemplate(false);
            }
            $em->persist($survey);
            $em->flush();

            $this->syncSurveyParticipations($survey, (array) $request->request->all('schools'), $schoolAuthority, $em);
            $em->flush();

            return $this->redirectToRoute('school_authority_survey_questions', ['id' => $survey->getId()]);
        }

        return $this->render('school_authority/survey/new.html.twig', [
            'form' => $form->createView(),
            'survey' => $survey,
            'schools' => $schools,
            'selectedSchoolIds' => $selectedSchoolIds,
            'isTemplate' => $kind === 'template',
        ]);
    }

    #[Route(path: '/umfragen/{id}/edit', name: 'school_authority_survey_edit')]
    public function edit(Survey $survey, Request $request, EntityManagerInterface $em, \Knp\Menu\MenuItem $menu): Response
    {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        if ($survey->getSchoolAuthority() !== $schoolAuthority) {
            throw $this->createAccessDeniedException();
        }
        if ($survey->getState() === Survey::STATE_CLOSED) {
            $this->addFlash('danger', 'Beendete Umfragen können nicht mehr bearbeitet werden.');
            return $this->redirectToRoute('school_authority_survey_show', ['id' => $survey->getId()]);
        }

        $menu['school_authority_surveys']->addChild('Umfrage bearbeiten', [
            'route' => 'school_authority_survey_edit',
            'routeParameters' => ['id' => $survey->getId()],
        ]);

        $form = $this->createForm(SchoolAuthoritySurveyType::class, $survey);
        $form->handleRequest($request);
        $schools = $schoolAuthority ? $schoolAuthority->getSchools() : [];
        $selectedSchoolIds = [];
        foreach ($survey->getSchoolParticipations() as $participation) {
            if ($participation->getSchool()) {
                $selectedSchoolIds[] = $participation->getSchool()->getId();
            }
        }
        if ($form->isSubmitted()) {
            $selectedSchoolIds = (array) $request->request->all('schools');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($survey->getSurveyTemplate()) {
                $survey->setClosesAt(null);
            } else {
                $survey->setSurveyTemplate(false);
            }
            $this->syncSurveyParticipations($survey, (array) $request->request->all('schools'), $schoolAuthority, $em);
            $em->persist($survey);
            $em->flush();

            $this->addFlash('success', 'Umfrage gespeichert.');

            return $this->redirectToRoute('school_authority_survey_show', ['id' => $survey->getId()]);
        }

        return $this->render('school_authority/survey/edit.html.twig', [
            'form' => $form->createView(),
            'survey' => $survey,
            'schools' => $schools,
            'selectedSchoolIds' => $selectedSchoolIds,
            'isTemplate' => (bool) $survey->getSurveyTemplate(),
        ]);
    }

    #[Route(path: '/umfragen/{id}', name: 'school_authority_survey_show')]
    public function show(Survey $survey, \Knp\Menu\MenuItem $menu): Response
    {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        if ($survey->getSchoolAuthority() !== $schoolAuthority) {
            throw $this->createAccessDeniedException();
        }

        $menu['school_authority_surveys']->addChild($survey->getName(), [
            'route' => 'school_authority_survey_show',
            'routeParameters' => ['id' => $survey->getId()],
        ]);

        $counts = [
            'questions' => $survey->getQuestions()->count(),
            'participants' => $survey->getNumberOfParticipants(),
            'schools' => $survey->getSchoolParticipations()->count(),
        ];
        $participationsDone = 0;
        foreach ($survey->getSchoolParticipations() as $participation) {
            if ($participation->getHasParticipated()) {
                $participationsDone++;
            }
        }
        $counts['schoolsParticipated'] = $participationsDone;
        $counts['schoolsOpen'] = $counts['schools'] - $participationsDone;

        return $this->render('school_authority/survey/show.html.twig', [
            'survey' => $survey,
            'counts' => $counts,
        ]);
    }

    #[Route(path: '/umfragen/{id}/schulen', name: 'school_authority_survey_schools')]
    public function selectSchools(
        Survey $survey,
        \Knp\Menu\MenuItem $menu
    ): Response {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        if (! ($survey->isSchoolAuthoritySurvey() || $survey->isSchoolAuthorityTemplate())
            || $survey->getSchoolAuthority() !== $schoolAuthority
        ) {
            throw $this->createAccessDeniedException();
        }

        $menu['school_authority_surveys']->addChild('Schulen auswählen', [
            'route' => 'school_authority_survey_schools',
            'routeParameters' => ['id' => $survey->getId()],
        ]);
        $this->addFlash('danger', 'Schulauswahl ist jetzt Teil des Umfrage-Formulars.');
        return $this->redirectToRoute('school_authority_survey_edit', ['id' => $survey->getId()]);
    }

    #[Route(path: '/umfragen/state/{state}/{id}', name: 'school_authority_survey_state')]
    public function state(int $state, Survey $survey, EntityManagerInterface $em, \Knp\Menu\MenuItem $menu): Response
    {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        if ($survey->getSchoolAuthority() !== $schoolAuthority) {
            throw $this->createAccessDeniedException();
        }

        $menu['school_authority_surveys']->addChild('Status', [
            'route' => 'school_authority_survey_state',
            'routeParameters' => ['id' => $survey->getId(), 'state' => $state],
        ]);

        if ($survey->isSchoolAuthorityTemplate()) {
            if ($survey->getState() === Survey::STATE_NOT_ACTIVATED && $state === Survey::STATE_ACTIVE) {
                $survey->setState(Survey::STATE_ACTIVE);
                $survey->setActivatedAt(new \DateTime());
            } elseif ($survey->getState() === Survey::STATE_ACTIVE && $state === Survey::STATE_NOT_ACTIVATED) {
                $survey->setState(Survey::STATE_NOT_ACTIVATED);
                $survey->setActivatedAt(null);
            }
        } else {
            if ($survey->getState() === Survey::STATE_NOT_ACTIVATED && $state === Survey::STATE_ACTIVE) {
                $survey->setState(Survey::STATE_ACTIVE);
                $survey->setActivatedAt(new \DateTime());

                // Reset answers and participants when starting again
                $survey->setNumberOfParticipants(0);
                foreach ($survey->getQuestions() as $question) {
                    $question->setAnswered(0);
                    $question->setNotAnswered(0);
                }
                if ($survey->getQuestions()->count() > 0) {
                    $em->createQueryBuilder()
                        ->delete(SurveyQuestionAnswer::class, 'a')
                        ->where('a.question IN (:questions)')
                        ->setParameter('questions', $survey->getQuestions())
                        ->getQuery()
                        ->execute();
                    $em->createQueryBuilder()
                        ->delete(SurveyQuestionChoiceAnswer::class, 'a')
                        ->where('a.question IN (:questions)')
                        ->setParameter('questions', $survey->getQuestions())
                        ->getQuery()
                        ->execute();
                }

                foreach ($survey->getSchoolParticipations() as $participation) {
                    $participation->setHasParticipated(false);
                    $participation->setParticipatedAt(null);
                }
            } elseif ($survey->getState() === Survey::STATE_ACTIVE && $state === Survey::STATE_CLOSED) {
                $survey->setState(Survey::STATE_CLOSED);
                $survey->setClosesAt(new \DateTime());
            } elseif ($survey->getState() === Survey::STATE_CLOSED && $state === Survey::STATE_ACTIVE) {
                $survey->setState(Survey::STATE_ACTIVE);
            }
        }

        $em->persist($survey);
        $em->flush();

        return $this->redirectToRoute('school_authority_surveys');
    }

    #[Route(path: '/umfragen/{id}/auswertung', name: 'school_authority_survey_result')]
    public function result(Survey $survey, \Knp\Menu\MenuItem $menu): Response
    {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        if ($survey->getSchoolAuthority() !== $schoolAuthority) {
            throw $this->createAccessDeniedException();
        }

        $menu['school_authority_surveys']->addChild($survey->getName() . ' Ergebnis', [
            'route' => 'school_authority_survey_result',
            'routeParameters' => ['id' => $survey->getId()],
        ]);

        $notAnswered = [];
        foreach ($survey->getQuestions() as $question) {
            $notAnswered[$question->getId()] = $question->getNotAnswered();
        }

        return $this->render('school_authority/survey/result.html.twig', [
            'survey' => $survey,
            'school' => $schoolAuthority ? $schoolAuthority->getName() : null,
            'participantCount' => $this->getSurveyParticipantCount($survey),
            'questionStats' => $this->buildSurveyQuestionStats($survey),
            'not_answered' => $notAnswered,
        ]);
    }

    #[Route(path: '/umfragen/{id}/vorschau', name: 'school_authority_survey_preview')]
    public function preview(Survey $survey, \Knp\Menu\MenuItem $menu): Response
    {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        if ($survey->getSchoolAuthority() !== $schoolAuthority) {
            throw $this->createAccessDeniedException();
        }

        $menu['school_authority_surveys']->addChild($survey->getName() . ' Vorschau', [
            'route' => 'school_authority_survey_preview',
            'routeParameters' => ['id' => $survey->getId()],
        ]);

        $form = $this->createFormBuilder(null, ['disabled' => true])->create('survey', FormType::class)->getForm();
        for ($i = 1; $i <= $survey->getQuestions()->count(); $i++) {
            foreach ($survey->getQuestions() as $question) {
                if ($question->getOrder() === $i) {
                    if ($question->getType() === \App\Entity\Survey\SurveyQuestion::TYPE_HAPPY_UNHAPPY && $question->getOrder() === $i) {
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
                            ],
                            'disabled' => true,
                        ]);
                    } elseif ($question->getType() === \App\Entity\Survey\SurveyQuestion::TYPE_SINGLE) {
                        $form->add($question->getId(), ChoiceType::class, [
                            'label' => $question->getQuestion(),
                            'placeholder' => 'Eine Option wählen:',
                            'required' => false,
                            'multiple' => false,
                            'expanded' => true,
                            'choices' => $question->getChoices4Form(),
                            'disabled' => true,
                        ]);
                    } elseif ($question->getType() === \App\Entity\Survey\SurveyQuestion::TYPE_MULTI) {
                        $form->add($question->getId(), ChoiceType::class, [
                            'label' => $question->getQuestion(),
                            'placeholder' => 'Eine Option wählen:',
                            'required' => false,
                            'multiple' => true,
                            'expanded' => true,
                            'choices' => $question->getChoices4Form(),
                            'disabled' => true,
                        ]);
                    } elseif ($question->getType() === \App\Entity\Survey\SurveyQuestion::TYPE_TEXT) {
                        $form->add($question->getId(), TextareaType::class, [
                            'label' => $question->getQuestion(),
                            'help' => 'Bitte beantworten sie die Frage mit einem kleinen Text:',
                            'required' => false,
                            'constraints' => [
                                new Length([
                                    'max' => 250,
                                    'maxMessage' => 'Die Antwort darf maximal {{ limit }} Zeichen lang sein.'
                                ]),
                            ],
                            'disabled' => true,
                        ]);
                    } else {
                        throw new \Exception('Type (' . $survey->getType() . ') not found!');
                    }
                }
            }
        }

        return $this->render('school_authority/survey/preview.html.twig', [
            'survey' => $survey,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param int[]|string[] $selectedSchoolIds
     */
    private function syncSurveyParticipations(Survey $survey, array $selectedSchoolIds, ?SchoolAuthority $schoolAuthority, EntityManagerInterface $em): void
    {
        $normalizedSelectedIds = [];
        foreach ($selectedSchoolIds as $schoolId) {
            $id = (int) $schoolId;
            if ($id > 0) {
                $normalizedSelectedIds[$id] = true;
            }
        }

        $existingBySchoolId = [];
        foreach ($survey->getSchoolParticipations()->toArray() as $participation) {
            $school = $participation->getSchool();
            if ($school === null) {
                $em->remove($participation);
                continue;
            }

            $schoolId = (int) $school->getId();
            $existingBySchoolId[$schoolId] = $participation;

            if (! isset($normalizedSelectedIds[$schoolId])) {
                $survey->removeSchoolParticipation($participation);
                $em->remove($participation);
            }
        }

        foreach (\array_keys($normalizedSelectedIds) as $schoolId) {
            if (isset($existingBySchoolId[$schoolId])) {
                continue;
            }

            $school = $em->getRepository(School::class)->find($schoolId);
            if (! $school || $school->getSchoolAuthority() !== $schoolAuthority) {
                continue;
            }

            $participation = new SurveySchoolParticipation();
            $participation->setSchool($school);
            $survey->addSchoolParticipation($participation);
            $em->persist($participation);
        }
    }

    private function getSurveyParticipantCount(Survey $survey): int
    {
        if ((int) $survey->getNumberOfParticipants() > 0) {
            return (int) $survey->getNumberOfParticipants();
        }

        $stats = $this->buildSurveyQuestionStats($survey);
        $maxTotal = 0;
        foreach ($stats as $item) {
            $total = (int) ($item['total'] ?? 0);
            if ($total > $maxTotal) {
                $maxTotal = $total;
            }
        }
        return $maxTotal;
    }

    private function buildSurveyQuestionStats(Survey $survey): array
    {
        $stats = [];
        $surveyParticipants = (int) $survey->getNumberOfParticipants();

        foreach ($survey->getQuestions() as $question) {
            $answered = $this->getQuestionAnsweredCount($question);
            $notAnswered = \max(0, (int) $question->getNotAnswered());
            $total = $surveyParticipants > 0 ? $surveyParticipants : ($answered + $notAnswered);
            if ($total < $answered) {
                $total = $answered;
            }
            $stats[$question->getId()] = [
                'answered' => $answered,
                'total' => $total,
            ];
        }

        return $stats;
    }

    private function getQuestionAnsweredCount(SurveyQuestion $question): int
    {
        if ($question->getType() === SurveyQuestion::TYPE_SINGLE || $question->getType() === SurveyQuestion::TYPE_MULTI) {
            $count = 0;
            foreach ($question->getChoices() as $choice) {
                $count += $choice->getAnswers()->count();
            }
            return $count;
        }

        return $question->getAnswers()->count();
    }
}
