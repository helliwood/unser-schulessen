<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-07
 * Time: 10:20
 */

namespace App\Controller\QualityCircle;

use App\Controller\AbstractController;
use App\Entity\QualityCircle\ToDoNew;
use App\Repository\QualityCircle\ToDoNewRepository;
use App\Service\QualityCheckService;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/quality_circle', name: 'quality_circle_')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_KITCHEN')]
class IndexController extends AbstractController
{
    /**
     * @var QualityCheckService
     */
    protected $qualityCheckService;

    /**
     * @var string
     */
    protected $stateCountry;

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
        $this->stateCountry = $params->get('app_state_country');
    }

    /**
     * @return Response|JsonResponse
     * @throws Exception
     */
    #[Route(path: '/', name: 'home')]
    public function index(Request $request): Response
    {
        /** @var ToDoNewRepository $tdr */
        $tdr = $this->getDoctrine()->getRepository(ToDoNew::class);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($tdr->find4Ajax(
                $this->getUser()->getCurrentSchool(),
                $request->query->get('sort', 'createdAt'),
                $request->query->getBoolean('sortDesc', true),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1),
                false
            ));
        }

        return $this->render('quality_circle/index/index.html.twig', [
            'flag_definitions' => $this->qualityCheckService->getFlagDefinitions(),
            'hasQualityCheck' => (bool) $this->qualityCheckService->getLastResult(),
        ]);
    }
}
