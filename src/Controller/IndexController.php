<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-26
 * Time: 14:30
 */

namespace App\Controller;

use App\Entity\School;
use App\Entity\UserHasSchool;
use App\Repository\UserHasSchoolRepository;
use App\Service\MasterDataService;
use App\Service\QualityCheckService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @param MasterDataService   $masterDataService
     * @param QualityCheckService $qualityCheckService
     * @return Response
     * @throws NonUniqueResultException
     */
    public function index(MasterDataService $masterDataService, QualityCheckService $qualityCheckService): Response
    {
        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
            'hasMasterData' => $masterDataService->hasFinalisedMasterData(),
            'hasQualityCheck' => ! \is_null($qualityCheckService->getLastResult()),
            'hasUpdatedMasterData' => $masterDataService->hasUpdatedMasterData(),
        ]);
    }

    /**
     * @Route("/accept_invite/{school}", name="accept_invite")
     * @param School $school
     * @return RedirectResponse
     * @throws \Exception
     */
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
     * @Route("/decline_invite/{school}", name="decline_invite")
     * @param School $school
     * @return RedirectResponse
     * @throws \Exception
     */
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
     * @Route("/change_school/{school}", name="change_school")
     * @param School $school
     * @return RedirectResponse
     */
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
        $token = $token instanceof SwitchUserToken ?
            new SwitchUserToken(
                $this->getUser(),
                $token->getCredentials(),
                $token->getProviderKey(),
                \array_merge($this->getUser()->getRoles(), ["ROLE_PREVIOUS_ADMIN"]),
                $token->getOriginalToken()
            )
            :
            new UsernamePasswordToken(
                $this->getUser(),
                $token->getCredentials(),
                $token->getProviderKey(),
                $this->getUser()->getRoles()
            );

        $this->container->get('security.token_storage')->setToken($token);
    }
}
