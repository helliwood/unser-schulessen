<?php

namespace App\Controller\SchoolAuthority;

use App\Entity\FoodSurvey\FoodSurvey;
use App\Entity\MasterData;
use App\Entity\QualityCheck\Result;
use App\Entity\School;
use App\Entity\SchoolYear;
use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveyQuestion;
use App\Entity\User;
use App\Entity\UserHasSchool;
use App\Form\SchoolAuthorityType;
use App\Repository\QualityCheck\ResultRepository;
use App\Repository\QualityCircle\ToDoRepository;
use App\Repository\SchoolRepository;
use App\Repository\Survey\SurveyRepository;
use App\Repository\Survey\SurveyVoucherRepository;
use App\Service\MasterDataService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Menu\MenuItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route(path: '/schultraeger')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_SCHOOL_AUTHORITY')]
class DashboardController extends AbstractController
{
    #[Route(path: '/', name: 'school_authority_dashboard')]
    public function dashboard(SurveyRepository $surveyRepository): Response
    {
        $user = $this->getUser();
        $schoolAuthority = $user ? $user->getSchoolAuthority() : null;
        $schoolCount = 0;
        $surveyCount = 0;
        if ($schoolAuthority) {
            $schoolCount = $schoolAuthority->getSchools()->count();
            $surveyCount = $surveyRepository->countBySchoolAuthority($schoolAuthority);
        }

        return $this->render('school_authority/dashboard/dashboard.html.twig', [
            'schoolAuthority' => $schoolAuthority,
            'schoolCount' => $schoolCount,
            'surveyCount' => $surveyCount,
        ]);
    }

    #[Route(path: '/profil', name: 'school_authority_profile')]
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $schoolAuthority = $user ? $user->getSchoolAuthority() : null;

        if (! $schoolAuthority) {
            $this->addFlash('danger', 'Sie sind keinem Schulträger zugeordnet.');
            return $this->redirectToRoute('school_authority_dashboard');
        }

        $form = $this->createForm(SchoolAuthorityType::class, $schoolAuthority);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($schoolAuthority);
            $em->flush();
            $this->addFlash('success', 'Die Stammdaten des Schulträgers wurden erfolgreich gespeichert.');
            return $this->redirectToRoute('school_authority_profile');
        }

        return $this->render('school_authority/dashboard/profile.html.twig', [
            'form' => $form->createView(),
            'schoolAuthority' => $schoolAuthority,
        ]);
    }

    #[Route(path: '/meine-schulen', name: 'school_authority_schools')]
    public function index(): Response
    {
        $user = $this->getUser();
        $schoolAuthority = $user->getSchoolAuthority();

        return $this->render('school_authority/dashboard/index.html.twig', [
            'schoolAuthority' => $schoolAuthority,
            'hasSchoolAuthority' => (bool) $schoolAuthority,
        ]);
    }

    #[Route(path: '/meine-schulen/ajax', name: 'school_authority_schools_ajax')]
    public function schoolsAjax(
        Request $request,
        SchoolRepository $schoolRepository,
        ResultRepository $resultRepository,
        ToDoRepository $toDoRepository,
        SurveyVoucherRepository $surveyVoucherRepository
    ): JsonResponse {
        $user = $this->getUser();
        $schoolAuthority = $user ? $user->getSchoolAuthority() : null;

        if (! $schoolAuthority) {
            return new JsonResponse([
                'totalRows' => 0,
                'items' => [],
            ]);
        }

        $search = \trim((string) $request->query->get('search', ''));
        $schools = $schoolRepository->findBySchoolAuthorityAndSearch($schoolAuthority, $search);

        $items = [];
        foreach ($schools as $school) {
            $latestResult = $resultRepository->findLatestBySchool($school);
            $latestToDo = $toDoRepository->findLatestBySchool($school);
            $latestSurveyResult = $surveyVoucherRepository->findLatestBySchool($school);
            $latestQcDate = $latestResult ? $latestResult->getFinalisedAt() : null;
            $latestToDoDate = $latestToDo ? $latestToDo->getCreatedAt() : null;
            $latestSurveyDate = $latestSurveyResult ? $latestSurveyResult->getCreatedAt() : null;

            $countQuestions = 0;
            $totalMainCategories = 0;
            $answeredMainCategories = 0;
            $countAnswered = 0;
            $countTrue = 0;
            $countPartial = 0;
            $countFalse = 0;
            $countNotAnswered = 0;
            $qcTruePercent = 0.0;
            $qcPartialPercent = 0.0;
            $qcFalsePercent = 0.0;
            $qcNotAnsweredPercent = 0.0;

            if ($latestResult) {
                $totalMainCategories = $latestResult->getQuestionnaire()
                    ? $latestResult->getQuestionnaire()->getCategories()->count()
                    : 0;
                $answeredMainCategories = $latestResult->countAnsweredMainCategories();
                $countQuestions = \count($latestResult->getQuestions());
                $countTrue = $latestResult->countAnswers(\App\Entity\QualityCheck\Result::ANSWER_TRUE);
                $countPartial = $latestResult->countAnswers(\App\Entity\QualityCheck\Result::ANSWER_PARTIAL);
                $countFalse = $latestResult->countAnswers(\App\Entity\QualityCheck\Result::ANSWER_FALSE);
                $countAnswered = $countTrue + $countPartial + $countFalse;
                $countNotAnswered = \max($countQuestions - $countAnswered, 0);

                if ($countQuestions > 0) {
                    $qcTruePercent = \round(($countTrue / $countQuestions) * 100, 2);
                    $qcPartialPercent = \round(($countPartial / $countQuestions) * 100, 2);
                    $qcFalsePercent = \round(($countFalse / $countQuestions) * 100, 2);
                    $qcNotAnsweredPercent = \round(($countNotAnswered / $countQuestions) * 100, 2);
                }
            }

            if (! $school->isSchoolAuthorityAccessAllowed()) {
                $items[] = [
                    'id' => $school->getId(),
                    'name' => $school->getName(),
                    'authorityAccessAllowed' => false,
                    'hasQualityCheck' => false,
                    'totalMainCategories' => 0,
                    'answeredMainCategories' => 0,
                    'countQuestions' => 0,
                    'countAnswered' => 0,
                    'countTrue' => 0,
                    'countPartial' => 0,
                    'countFalse' => 0,
                    'countNotAnswered' => 0,
                    'qcTrue' => 0,
                    'qcPartial' => 0,
                    'qcFalse' => 0,
                    'qcNotAnswered' => 0,
                    'qualityCheck' => 0,
                    'latestQcDate' => '-',
                    'latestQcDateSort' => 0,
                    'latestToDoDate' => '-',
                    'latestToDoDateSort' => 0,
                    'latestSurveyDate' => '-',
                    'latestSurveyDateSort' => 0,
                ];
                continue;
            }

            $items[] = [
                'id' => $school->getId(),
                'name' => $school->getName(),
                'authorityAccessAllowed' => $school->isSchoolAuthorityAccessAllowed(),
                'hasQualityCheck' => $latestResult && $countQuestions > 0,
                'totalMainCategories' => $totalMainCategories,
                'answeredMainCategories' => $answeredMainCategories,
                'countQuestions' => $countQuestions,
                'countAnswered' => $countAnswered,
                'countTrue' => $countTrue,
                'countPartial' => $countPartial,
                'countFalse' => $countFalse,
                'countNotAnswered' => $countNotAnswered,
                'qcTrue' => $qcTruePercent,
                'qcPartial' => $qcPartialPercent,
                'qcFalse' => $qcFalsePercent,
                'qcNotAnswered' => $qcNotAnsweredPercent,
                'qualityCheck' => $qcTruePercent,
                'latestQcDate' => $latestQcDate ? $latestQcDate->format('d.m.Y') : '-',
                'latestQcDateSort' => $latestQcDate ? $latestQcDate->getTimestamp() : 0,
                'latestToDoDate' => $latestToDoDate ? $latestToDoDate->format('d.m.Y') : '-',
                'latestToDoDateSort' => $latestToDoDate ? $latestToDoDate->getTimestamp() : 0,
                'latestSurveyDate' => $latestSurveyDate ? $latestSurveyDate->format('d.m.Y') : '-',
                'latestSurveyDateSort' => $latestSurveyDate ? $latestSurveyDate->getTimestamp() : 0,
            ];
        }

        $sort = $request->query->get('sort', 'name');
        $sortDesc = $request->query->getBoolean('sortDesc', false);
        $page = \max(1, $request->query->getInt('page', 1));
        $size = \max(1, $request->query->getInt('size', 10));

        $sortValues = ['name', 'qualityCheck', 'latestQcDate', 'latestToDoDate', 'latestSurveyDate'];
        if (! \in_array($sort, $sortValues, true)) {
            $sort = 'name';
        }

        \usort($items, static function (array $a, array $b) use ($sort, $sortDesc): int {
            switch ($sort) {
                case 'qualityCheck':
                    $left = $a['qualityCheck'];
                    $right = $b['qualityCheck'];
                    break;
                case 'latestQcDate':
                    $left = $a['latestQcDateSort'];
                    $right = $b['latestQcDateSort'];
                    break;
                case 'latestToDoDate':
                    $left = $a['latestToDoDateSort'];
                    $right = $b['latestToDoDateSort'];
                    break;
                case 'latestSurveyDate':
                    $left = $a['latestSurveyDateSort'];
                    $right = $b['latestSurveyDateSort'];
                    break;
                case 'name':
                default:
                    $left = \mb_strtolower($a['name']);
                    $right = \mb_strtolower($b['name']);
                    break;
            }

            $result = $left <=> $right;
            return $sortDesc ? -$result : $result;
        });

        $totalRows = \count($items);
        $items = \array_slice($items, ($page - 1) * $size, $size);

        return new JsonResponse([
            'totalRows' => $totalRows,
            'items' => \array_values($items),
        ]);
    }

    #[Route(path: '/schule/{id}', name: 'school_authority_school_details')]
    public function schoolDetails(
        School $school,
        ResultRepository $resultRepository,
        ToDoRepository $toDoRepository,
        SurveyRepository $surveyRepository,
        MenuItem $menu
    ): Response {
        $user = $this->getUser();
        $schoolAuthority = $user->getSchoolAuthority();

        if (! $schoolAuthority
            || ! $schoolAuthority->getSchools()->contains($school)
            || ! $school->isSchoolAuthorityAccessAllowed()
        ) {
            throw $this->createAccessDeniedException('Sie haben keine Berechtigung, diese Schule einzusehen.');
        }

        $menu['school_authority_schools']->addChild($school->getName(), [
            'route' => 'school_authority_school_details',
            'routeParameters' => ['id' => $school->getId()]
        ]);

        $results = $resultRepository->findBy(['school' => $school, 'finalised' => true], ['createdAt' => 'DESC']);
        $latestResult = $resultRepository->findLatestBySchool($school);
        $latestToDo = $toDoRepository->findLatestBySchool($school);
        $closedSurveys = $surveyRepository->findBy(
            ['school' => $school, 'state' => Survey::STATE_CLOSED, 'surveyTemplate' => false],
            ['closesAt' => 'DESC', 'createdAt' => 'DESC'],
            3
        );
        $closedSurveyItems = [];
        foreach ($closedSurveys as $closedSurvey) {
            $closedSurveyItems[] = [
                'survey' => $closedSurvey,
                'participants' => $this->getSurveyParticipantCount($closedSurvey),
            ];
        }

        $qualityCheckCount = \count($results);

        $surveyTotal = 0;
        $surveyActive = 0;
        $surveyClosed = 0;
        foreach ($school->getSurveys() as $survey) {
            if ($survey->getSurveyTemplate()) {
                continue;
            }
            $surveyTotal++;
            if ($survey->getState() === Survey::STATE_ACTIVE) {
                $surveyActive++;
            } elseif ($survey->getState() === Survey::STATE_CLOSED) {
                $surveyClosed++;
            }
        }

        $foodSurveyTotal = 0;
        $foodSurveyActive = 0;
        $foodSurveyClosed = 0;
        $closedFoodSurveyItems = [];
        foreach ($school->getFoodSurveys() as $foodSurvey) {
            $foodSurveyTotal++;
            if ($foodSurvey->getState() === FoodSurvey::STATE_ACTIVE) {
                $foodSurveyActive++;
            } elseif ($foodSurvey->getState() === FoodSurvey::STATE_CLOSED) {
                $foodSurveyClosed++;
                $closedFoodSurveyItems[] = $foodSurvey;
            }
        }
        \usort($closedFoodSurveyItems, static function (FoodSurvey $left, FoodSurvey $right): int {
            $leftTime = $left->getClosesAt() ? $left->getClosesAt()->getTimestamp() : 0;
            $rightTime = $right->getClosesAt() ? $right->getClosesAt()->getTimestamp() : 0;
            if ($leftTime === $rightTime) {
                $leftCreated = $left->getCreatedAt() ? $left->getCreatedAt()->getTimestamp() : 0;
                $rightCreated = $right->getCreatedAt() ? $right->getCreatedAt()->getTimestamp() : 0;
                return $rightCreated <=> $leftCreated;
            }
            return $rightTime <=> $leftTime;
        });
        $closedFoodSurveyItems = \array_slice($closedFoodSurveyItems, 0, 3);

        $membersTotal = $school->getUserHasSchool()->count();
        $membersAccepted = $school->getUserHasSchool()->filter(static function ($item) {
            return $item->getState() === \App\Entity\UserHasSchool::STATE_ACCEPTED;
        })->count();
        $schoolAuthorityMembers = [];
        foreach ($school->getUserHasSchool() as $userHasSchool) {
            if (! $userHasSchool instanceof UserHasSchool) {
                continue;
            }
            if ($userHasSchool->getState() !== UserHasSchool::STATE_ACCEPTED) {
                continue;
            }
            $role = $userHasSchool->getRole();
            if ($role !== User::ROLE_SCHOOL_AUTHORITIES && $role !== User::ROLE_SCHOOL_AUTHORITIES_ACTIVE) {
                continue;
            }
            $memberUser = $userHasSchool->getUser();
            if ($memberUser === null) {
                continue;
            }

            $displayName = $memberUser->getDisplayName();
            if ($displayName === '') {
                $displayName = $memberUser->getEmail() ?? '-';
            }

            $schoolAuthorityMembers[] = [
                'displayName' => $displayName,
                'email' => $memberUser->getEmail() ?? '-',
                'roleLabel' => UserHasSchool::ROLE_LABELS[$role] ?? $role,
            ];
        }
        \usort($schoolAuthorityMembers, static function (array $left, array $right): int {
            return \mb_strtolower($left['displayName']) <=> \mb_strtolower($right['displayName']);
        });

        return $this->render('school_authority/dashboard/school_details.html.twig', [
            'school' => $school,
            'results' => $results,
            'closedSurveyItems' => $closedSurveyItems,
            'closedFoodSurveyItems' => $closedFoodSurveyItems,
            'schoolAuthorityMembers' => $schoolAuthorityMembers,
            'latestResult' => $latestResult,
            'stats' => $latestResult ? $latestResult->getGaugeStats() : null,
            'summary' => [
                'ourSchool' => [
                    'masterDataCount' => $school->getMasterData()->count(),
                    'membersTotal' => $membersTotal,
                    'membersAccepted' => $membersAccepted,
                ],
                'qualityCheck' => [
                    'count' => $qualityCheckCount,
                    'lastDate' => $latestResult ? $latestResult->getFinalisedAt() : null,
                ],
                'qualityCircle' => [
                    'count' => $toDoRepository->countBySchool($school),
                    'openCount' => $toDoRepository->countOpenBySchool($school),
                    'lastDate' => $latestToDo ? $latestToDo->getCreatedAt() : null,
                ],
                'survey' => [
                    'count' => $surveyTotal,
                    'active' => $surveyActive,
                    'closed' => $surveyClosed,
                ],
                'foodSurvey' => [
                    'count' => $foodSurveyTotal,
                    'active' => $foodSurveyActive,
                    'closed' => $foodSurveyClosed,
                ],
            ],
        ]);
    }

    #[Route(path: '/schule/{id}/stammdaten-pdf', name: 'school_authority_school_master_data_export')]
    public function schoolMasterDataExport(School $school, MasterDataService $masterDataService): Response
    {
        $user = $this->getUser();
        $schoolAuthority = $user->getSchoolAuthority();

        if (! $schoolAuthority
            || ! $schoolAuthority->getSchools()->contains($school)
            || ! $school->isSchoolAuthorityAccessAllowed()
        ) {
            throw $this->createAccessDeniedException('Sie haben keine Berechtigung, diese Schule einzusehen.');
        }

        $schoolYearRepository = $this->getDoctrine()->getRepository(SchoolYear::class);
        $currentSchoolYear = $schoolYearRepository->findCurrent();
        if (! $currentSchoolYear) {
            $currentSchoolYear = $masterDataService->addMissingSchoolYear();
        }

        $masterDataRepository = $this->getDoctrine()->getRepository(MasterData::class);
        $masterData = $masterDataRepository->findOneBy([
            'school' => $school,
            'schoolYear' => $currentSchoolYear,
            'finalised' => true,
        ]);

        if (! $masterData instanceof MasterData) {
            $this->addFlash('danger', 'Für das aktuelle Schuljahr liegen keine finalisierten Stammdaten vor.');
            return $this->redirectToRoute('school_authority_school_details', ['id' => $school->getId()]);
        }

        $options = new Options();
        $options->setChroot('/var/www/public');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->render('pdf/master_data.html.twig', [
            'currentSchool' => $school,
            'topic' => $school . ': Stammdaten',
            'data' => $masterDataService->getDataForMasterData($masterData),
        ])->getContent());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $slugger = new AsciiSlugger();
        $safeSchoolName = (string) $slugger->slug($school->getName(), '_')->lower();
        if ($safeSchoolName === '') {
            $safeSchoolName = 'schule_' . $school->getId();
        }

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $safeSchoolName . '.pdf"',
        ]);
    }

    #[Route(path: '/schule/{school}/umfragen/{survey}/auswertung', name: 'school_authority_school_survey_result')]
    public function schoolSurveyResult(School $school, Survey $survey, MenuItem $menu): Response
    {
        $user = $this->getUser();
        $schoolAuthority = $user->getSchoolAuthority();

        if (! $schoolAuthority
            || ! $schoolAuthority->getSchools()->contains($school)
            || ! $school->isSchoolAuthorityAccessAllowed()
            || $survey->getSchool() !== $school
            || $survey->getState() !== Survey::STATE_CLOSED
        ) {
            throw $this->createAccessDeniedException('Sie haben keine Berechtigung, diese Umfrage auszuwerten.');
        }

        $menu['school_authority_schools']->addChild($school->getName(), [
            'route' => 'school_authority_school_details',
            'routeParameters' => ['id' => $school->getId()],
        ])->addChild('Umfrage-Auswertung', [
            'route' => 'school_authority_school_survey_result',
            'routeParameters' => ['school' => $school->getId(), 'survey' => $survey->getId()],
        ]);

        $notAnswered = [];
        foreach ($survey->getQuestions() as $question) {
            $notAnswered[$question->getId()] = $question->getNotAnswered();
        }

        return $this->render('school_authority/dashboard/survey_result.html.twig', [
            'school' => $school,
            'survey' => $survey,
            'participantCount' => $this->getSurveyParticipantCount($survey),
            'questionStats' => $this->buildSurveyQuestionStats($survey),
            'not_answered' => $notAnswered,
        ]);
    }

    #[Route(path: '/schule/{school}/teller-check/{foodSurvey}/auswertung', name: 'school_authority_school_food_survey_result')]
    public function schoolFoodSurveyResult(School $school, FoodSurvey $foodSurvey, MenuItem $menu): Response
    {
        $user = $this->getUser();
        $schoolAuthority = $user->getSchoolAuthority();

        if (! $schoolAuthority
            || ! $schoolAuthority->getSchools()->contains($school)
            || ! $school->isSchoolAuthorityAccessAllowed()
            || $foodSurvey->getSchool() !== $school
            || $foodSurvey->getState() !== FoodSurvey::STATE_CLOSED
        ) {
            throw $this->createAccessDeniedException('Sie haben keine Berechtigung, diesen Teller-Check auszuwerten.');
        }

        $menu['school_authority_schools']->addChild($school->getName(), [
            'route' => 'school_authority_school_details',
            'routeParameters' => ['id' => $school->getId()],
        ])->addChild('Teller-Check-Auswertung', [
            'route' => 'school_authority_school_food_survey_result',
            'routeParameters' => ['school' => $school->getId(), 'foodSurvey' => $foodSurvey->getId()],
        ]);

        return $this->render('school_authority/dashboard/food_survey_result.html.twig', [
            'school' => $school,
            'foodSurvey' => $foodSurvey,
        ]);
    }

    #[Route(path: '/schule/{id}/qc-gauge', name: 'school_authority_school_qc_gauge')]
    public function schoolQualityCheckGauge(School $school, ResultRepository $resultRepository): Response
    {
        $user = $this->getUser();
        $schoolAuthority = $user->getSchoolAuthority();

        if (! $schoolAuthority
            || ! $schoolAuthority->getSchools()->contains($school)
            || ! $school->isSchoolAuthorityAccessAllowed()
        ) {
            throw $this->createAccessDeniedException('Sie haben keine Berechtigung, diese Schule einzusehen.');
        }

        $result = $resultRepository->findLatestBySchool($school);
        if (! $result) {
            return $this->renderEmptyGauge();
        }

        $countTrue = $countPartial = $countFalse = 0;
        foreach ($result->getAnsweredCategories() as $answeredCategory) {
            $countTrue += $answeredCategory->countAnswers($result, Result::ANSWER_TRUE);
            $countPartial += $answeredCategory->countAnswers($result, Result::ANSWER_PARTIAL);
            $countFalse += $answeredCategory->countAnswers($result, Result::ANSWER_FALSE);
        }

        $total = \count($result->getQuestions());
        if ($total === 0) {
            return $this->renderEmptyGauge();
        }

        $svg = '';
        $currentPercent = 0.0;

        if ($countTrue > 0) {
            $percent = ($countTrue / $total * 100) / 2;
            $svg .= '<circle class="donut-segment" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#04B100" stroke-width="10" stroke-dasharray="' . $percent . ' ' . (100 - $percent) . '" stroke-dashoffset="' . (50 - $currentPercent) . '"></circle>';
            $currentPercent += $percent;
        }
        if ($countPartial > 0) {
            $percent = ($countPartial / $total * 100) / 2;
            $svg .= '<circle class="donut-segment" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#FFA700" stroke-width="10" stroke-dasharray="' . $percent . ' ' . (100 - $percent) . '" stroke-dashoffset="' . (50 - $currentPercent) . '"></circle>';
            $currentPercent += $percent;
        }
        if ($countFalse > 0) {
            $percent = ($countFalse / $total * 100) / 2;
            $svg .= '<circle class="donut-segment" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#D30000" stroke-width="10" stroke-dasharray="' . $percent . ' ' . (100 - $percent) . '" stroke-dashoffset="' . (50 - $currentPercent) . '"></circle>';
        }

        $linesSvg = '';
        for ($i = 0; $i <= $total; $i++) {
            $linesSvg .= '<line x1="25" y1="4" x2="25" y2="6" stroke-width="0.25" stroke="#DDD" transform="rotate(' . (180 / $total * $i) . ' 25 25)"></line>';
        }

        return new Response('<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <svg viewBox="0 0 50 25" width="1000" height="500" xmlns="http://www.w3.org/2000/svg">
            <circle class="donut-ring" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#EBEBEB" stroke-width="10"></circle>
            ' . $svg . '
            <g transform="rotate(-90 25 25)">
            ' . $linesSvg . '
            </g>
        </svg>', 200, ['Content-Type' => 'image/svg+xml']);
    }

    private function renderEmptyGauge(): Response
    {
        return new Response('<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <svg viewBox="0 0 50 25" width="1000" height="500" xmlns="http://www.w3.org/2000/svg">
            <circle class="donut-ring" cx="25" cy="25" r="15.91549430918954" fill="transparent" stroke="#EBEBEB" stroke-width="10"></circle>
        </svg>', 200, ['Content-Type' => 'image/svg+xml']);
    }

    #[Route(path: '/schule/{id}/umfragen/ajax', name: 'school_authority_school_surveys_ajax')]
    public function schoolSurveysAjax(School $school, Request $request, SurveyRepository $surveyRepository): JsonResponse
    {
        $user = $this->getUser();
        $schoolAuthority = $user->getSchoolAuthority();

        if (! $schoolAuthority
            || ! $schoolAuthority->getSchools()->contains($school)
            || ! $school->isSchoolAuthorityAccessAllowed()
        ) {
            throw $this->createAccessDeniedException('Sie haben keine Berechtigung, diese Schule einzusehen.');
        }

        $sort = $request->query->get('sort', 'createdAt');
        $sortDesc = $request->query->getBoolean('sortDesc', true);
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $closedOnly = $request->query->getBoolean('closedOnly', true);

        $result = $surveyRepository->find4Ajax(
            $school,
            $sort,
            $sortDesc,
            $page,
            $limit,
            $closedOnly
        );

        return new JsonResponse($result);
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
