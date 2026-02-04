<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-26
 * Time: 14:30
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Repository\MasterDataRepository;
use App\Repository\QualityCheck\ResultRepository;
use App\Repository\QuestionnaireRepository;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\Survey\CategoryRepository;
use App\Repository\Survey\SurveyQuestionRepository;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin", name="admin_")
 * @IsGranted("ROLE_ADMIN")
 */
class IndexController extends AbstractController
{

    /**
     * @Route("/{selectedYear}", name="home", defaults={"selectedYear":null}, requirements={"selectedYear"="\d+"})
     * @param string|null $selectedYear
     * @param QuestionnaireRepository $qr
     * @param ResultRepository $rr
     * @param SchoolRepository $sr
     * @param SurveyQuestionRepository $surQuesRep
     * @param CategoryRepository $cr
     * @param UserRepository $ur
     * @param MasterDataRepository $mr
     * @return Response
     * @throws \DateMalformedPeriodStringException
     */
    public function index(
        ?string $selectedYear,
        QuestionnaireRepository $qr,
        ResultRepository $rr,
        SchoolRepository $sr,
        SurveyQuestionRepository $surQuesRep,
        CategoryRepository $cr,
        UserRepository $ur,
        MasterDataRepository $mr,
        SchoolYearRepository $syr
    ): Response {
        
        $currentYear = (int) \date('Y');
        $currentMonth = (int) \date('m');

        // Ab September zählt das neue Schuljahr
        $schoolYear = $currentMonth >= 9 ? $currentYear : $currentYear - 1;


        // Lese das erste Schuljahr aus der Datenbank
        $firstSchoolYear = $syr->findOneBy([], ['year' => 'ASC']);
        $firstYear = $firstSchoolYear ? $firstSchoolYear->getYear() : $schoolYear;

        // Verwende das ausgewählte Jahr aus der URL oder falls nicht vorhanden das erste Jahr aus der DB
        $selectedYear = ! \is_null($selectedYear) ? (int)$selectedYear : $schoolYear;

        return $this->render('admin/index/index.html.twig', [
            'newMemberStats' => $ur->getNewMemberStats(),
            'masterDataStats' => $mr->getMasterDataStats($selectedYear),
            'surveyStats' => $surQuesRep->getSurveyQuestionStats($selectedYear),
            'resultStats' => $rr->getResultStats($selectedYear),
            'questionnaireCount' => $qr->count([]),
            'schoolCount' => $sr->count([]),
            'schoolMiniCheckCount' => $sr->count(["miniCheck" => true]),
            'surveyCategoryCount' => $cr->count([]),
            'userHasSchoolCount' => \count($ur->findBy(['employee' => true])),
            'state_country' => $this->getStateCountry(),
            'selectedYear' => $selectedYear,
            'schoolYear' => $schoolYear,
            'firstSchoolYear' => $firstSchoolYear
        ]);
    }
}
