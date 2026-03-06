<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-26
 * Time: 14:30
 */

namespace App\Controller;

use App\Entity\School;
use App\Entity\Survey\SurveySchoolParticipation;
use App\Entity\UserHasSchool;
use App\Repository\Survey\SurveySchoolParticipationRepository;
use App\Repository\UserHasSchoolRepository;
use App\Service\MasterDataService;
use App\Service\QualityCheckService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class IndexController extends AbstractController
{
    /**
     * @param MasterDataService   $masterDataService
     * @param QualityCheckService $qualityCheckService
     * @return Response
     * @throws NonUniqueResultException
     */
    #[Route(path: '/', name: 'home')]
    public function index(
        Request $request,
        MasterDataService $masterDataService,
        QualityCheckService $qualityCheckService
    ): Response {
        if ($this->isGranted('ROLE_SCHOOL_AUTHORITY')) {
            return $this->redirectToRoute('school_authority_dashboard');
        }

        $showMasterDataModal = false;
        if (! $masterDataService->hasUpdatedMasterData()
            && ($this->isGranted('ROLE_FOOD_COMMISSIONER') || $this->isGranted('ROLE_SCHOOL_AUTHORITIES_ACTIVE'))
        ) {
            $session = $request->getSession();
            $flagKey = 'masterdata_modal_shown';
            $showMasterDataModal = ! $session->has($flagKey);
            if ($showMasterDataModal) {
                $session->set($flagKey, true);
            }
        }

        $currentSchool = $this->getUser() ? $this->getUser()->getCurrentSchool() : null;
        $authorityOpenSurveyParticipations = [];
        $authorityCompletedSurveyParticipations = [];
        if ($currentSchool) {
            /** @var SurveySchoolParticipationRepository $spr */
            $spr = $this->getDoctrine()->getRepository(SurveySchoolParticipation::class);
            $authorityOpenSurveyParticipations = $spr->findActiveSurveysBySchool($currentSchool);
            $authorityOpenSurveyParticipations = \array_values(\array_filter(
                $authorityOpenSurveyParticipations,
                static function (SurveySchoolParticipation $sp) use ($currentSchool): bool {
                    return $sp->getSurvey()
                        && $sp->getSchool()
                        && $sp->getSchool()->getId() === $currentSchool->getId()
                        && $sp->getSurvey()->isSchoolAuthoritySurvey()
                        && ! $sp->getSurvey()->getSurveyTemplate();
                }
            ));

            // Guard against duplicate participation rows for the same survey-school relation.
            $uniqueOpenParticipations = [];
            foreach ($authorityOpenSurveyParticipations as $participation) {
                $survey = $participation->getSurvey();
                if ($survey === null) {
                    continue;
                }
                $uniqueOpenParticipations[$survey->getId()] = $participation;
            }
            $authorityOpenSurveyParticipations = \array_values($uniqueOpenParticipations);

            $authorityCompletedSurveyParticipations = $spr->findParticipatedSurveysBySchool($currentSchool);
            $authorityCompletedSurveyParticipations = \array_values(\array_filter(
                $authorityCompletedSurveyParticipations,
                static function (SurveySchoolParticipation $sp) use ($currentSchool): bool {
                    return $sp->getSurvey()
                        && $sp->getSchool()
                        && $sp->getSchool()->getId() === $currentSchool->getId()
                        && $sp->getSurvey()->isSchoolAuthoritySurvey()
                        && ! $sp->getSurvey()->getSurveyTemplate();
                }
            ));
            $uniqueCompletedParticipations = [];
            foreach ($authorityCompletedSurveyParticipations as $participation) {
                $survey = $participation->getSurvey();
                if ($survey === null) {
                    continue;
                }
                $uniqueCompletedParticipations[$survey->getId()] = $participation;
            }
            $authorityCompletedSurveyParticipations = \array_values($uniqueCompletedParticipations);
        }

        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
            'hasMasterData' => $masterDataService->hasFinalisedMasterData(),
            'hasQualityCheck' => ! \is_null($qualityCheckService->getLastResult()),
            'hasUpdatedMasterData' => $masterDataService->hasUpdatedMasterData(),
            'showMasterDataModal' => $showMasterDataModal,
            'authorityOpenSurveyParticipations' => $authorityOpenSurveyParticipations,
            'authorityCompletedSurveyParticipations' => $authorityCompletedSurveyParticipations,
        ]);
    }

    /**
     * @param School $school
     * @return RedirectResponse
     * @throws \Exception
     */
    #[Route(path: '/accept_invite/{school}', name: 'accept_invite')]
    public function acceptInvite(School $school): RedirectResponse
    {
        /** @var UserHasSchoolRepository $ur */
        $uhsr = $this->getDoctrine()->getRepository(UserHasSchool::class);

        $uhs = $uhsr->find(['user' => $this->getUser(), 'school' => $school]);
        $uhs->setState(UserHasSchool::STATE_ACCEPTED);
        $this->refreshToken();
        $this->getDoctrine()->getManager()->flush($uhs);

        $this->getSuccessMessage('Die Anfrage wurde angenommen!');

        return $this->redirectToRoute('home');
    }

    /**
     * @param School $school
     * @return RedirectResponse
     * @throws \Exception
     */
    #[Route(path: '/decline_invite/{school}', name: 'decline_invite')]
    public function declineInvite(School $school): RedirectResponse
    {
        /** @var UserHasSchoolRepository $ur */
        $uhsr = $this->getDoctrine()->getRepository(UserHasSchool::class);

        $uhs = $uhsr->find(['user' => $this->getUser(), 'school' => $school]);
        $uhs->setState(UserHasSchool::STATE_REJECTED);
        $this->refreshToken();
        $this->getDoctrine()->getManager()->flush($uhs);

        $this->getErrorMessage('Die Anfrage wurde abgelehnt!');

        return $this->redirectToRoute('home');
    }

    /**
     * @param School $school
     * @return RedirectResponse
     */
    #[Route(path: '/change_school/{school}', name: 'change_school')]
    public function changeSchool(School $school): RedirectResponse
    {
        try {
            $this->getUser()->setCurrentSchool($school);
            $this->refreshToken();
            $this->getDoctrine()->getManager()->flush();
            $this->getSuccessMessage('Schule gewechselt!');
        } catch (\Throwable $e) {
            $this->getErrorMessage('Schule nicht gefunden!');
        }
        return $this->redirectToRoute('home');
    }

    /**
     * @throws \Exception
     */
    protected function refreshToken(): void
    {
        // Session aktualisieren, sonst fliegt der User nach Redirekt raus
        // always_authenticate_before_granting=true kann leider nicht verwendet werden
        // bug: https://github.com/symfony/symfony/issues/32756
        $token = $this->container->get('security.token_storage')->getToken();
        $firewallName = \method_exists($token, 'getFirewallName') ? $token->getFirewallName() : 'main';
        $token = $token instanceof SwitchUserToken ?
            new SwitchUserToken(
                $this->getUser(),
                $firewallName,
                \array_merge($this->getUser()->getRoles(), ["ROLE_PREVIOUS_ADMIN"]),
                $token->getOriginalToken(),
            )
            :
            new UsernamePasswordToken(
                $this->getUser(),
                $firewallName,
                $this->getUser()->getRoles(),
            );

        $this->container->get('security.token_storage')->setToken($token);
    }
}
