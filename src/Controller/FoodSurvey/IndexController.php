<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2022-09-15
 * Time: 11:20
 */

namespace App\Controller\FoodSurvey;

use App\Controller\AbstractController;
use App\Entity\FoodSurvey\FoodSurvey;
use App\Entity\FoodSurvey\FoodSurveySpot;
use App\Form\FoodSurveyCloneType;
use App\Repository\FoodSurvey\FoodSurveyRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Imagick;
use ImagickDrawException;
use ImagickException;
use Knp\Menu\MenuItem;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/food-survey", name="food_survey_")
 * @IsGranted("ROLE_USER")
 */
class IndexController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @return Response
     * @throws Exception
     */
    public function index(): Response
    {
        return $this->render('food_survey/index/index.html.twig', [
            'school' => $this->getUser()->getCurrentSchool()
        ]);
    }

    /**
     * @Route("/list", name="list")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    public function list(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var FoodSurveyRepository $sr */
        $sr = $entityManager->getRepository(FoodSurvey::class);
        return new JsonResponse($sr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'createdAt'),
            $request->query->getBoolean('sortDesc', true),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1)
        ));
    }

    /**
     * @Route("/list-closed", name="list_closed")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function listClosed(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var FoodSurveyRepository $sr */
        $sr = $entityManager->getRepository(FoodSurvey::class);
        return new JsonResponse($sr->find4Ajax(
            $this->getUser()->getCurrentSchool(),
            $request->query->get('sort', 'createdAt'),
            $request->query->getBoolean('sortDesc', true),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 1),
            true
        ));
    }

    /**
     * @Route("/state/{state}/{id}", name="state")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_KITCHEN') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE') or is_granted('ROLE_FOOD_COMMISSIONER')")
     * @param int $state
     * @param FoodSurvey $foodSurvey
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function state(int $state, FoodSurvey $foodSurvey, EntityManagerInterface $entityManager): Response
    {
        if ($foodSurvey->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        if ($foodSurvey->getState() === FoodSurvey::STATE_NOT_ACTIVATED && $state === FoodSurvey::STATE_ACTIVE) {
            $foodSurvey->setState(FoodSurvey::STATE_ACTIVE);
            $foodSurvey->setActivatedAt(new \DateTime());

            foreach ($foodSurvey->getResults() as $result) {
                $entityManager->remove($result);
            }
            $foodSurvey->getResults()->clear();
        }
        if ($foodSurvey->getState() === FoodSurvey::STATE_ACTIVE && $state === FoodSurvey::STATE_CLOSED) {
            $foodSurvey->setState(FoodSurvey::STATE_CLOSED);
            $foodSurvey->setClosesAt(new \DateTime());
        }
        if ($foodSurvey->getState() === FoodSurvey::STATE_CLOSED && $state === FoodSurvey::STATE_ACTIVE) {
            $foodSurvey->setState(FoodSurvey::STATE_ACTIVE);
        }
        $entityManager->persist($foodSurvey);
        $entityManager->flush();

        return $this->redirectToRoute('food_survey_home');
    }

    /**
     * @Route("/delete/{id}", name="delete")
     * @Security("is_granted('ROLE_MENSA_AG') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param FoodSurvey $foodSurvey
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function delete(FoodSurvey $foodSurvey, EntityManagerInterface $entityManager): Response
    {
        if ($foodSurvey->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Schule nicht gestattet.');
        }

        if ($foodSurvey->getState() === FoodSurvey::STATE_NOT_ACTIVATED) {
            $entityManager->remove($foodSurvey);
            $entityManager->flush();
        }

        return $this->redirectToRoute('food_survey_home');
    }

    /**
     * @Route("/creator/{id}", name="creator", defaults={"id":null})
     * @param FoodSurvey|null $foodSurvey
     * @param MenuItem $menu
     * @return Response
     * @throws Exception
     */
    public function creator(?FoodSurvey $foodSurvey, MenuItem $menu): Response
    {

        $menu['food-survey']->addChild('Neuer Teller-Check', [
            'route' => 'food_survey_creator',
        ]);

        return $this->render('food_survey/index/creator.html.twig', [
            'school' => $this->getUser()->getCurrentSchool(),
            'foodSurvey' => $foodSurvey
        ]);
    }

    /**
     * @Route("/clone", name="clone")
     * @param MenuItem $menu
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     */
    public function clone(MenuItem $menu, Request $request, EntityManagerInterface $em): Response
    {

        $menu['food-survey']->addChild('Teller-Check kopieren', [
            'route' => 'food_survey_creator',
        ]);

        $templateSurvey = null;
        $form = $this->createForm(FoodSurveyCloneType::class);

        $form->handleRequest($request);
        if (! \is_null($form->get('template')->getData())) {
            try {
                $uuid = Uuid::fromString($form->get('template')->getData());
                $templateSurvey = $em->getRepository(FoodSurvey::class)->findOneBy(['uuid' => $uuid]);
                if (\is_null($templateSurvey)) {
                    $form->get('template')->addError(new FormError('Template nicht gefunden!'));
                }
            } catch (\Throwable $e) {
                $form->get('template')->addError(new FormError($e->getMessage()));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();
            $conn->beginTransaction();
            try {
                $survey = clone $templateSurvey;

                $survey->setName($form->get('name')->getData());
                $survey->setCreatedBy($this->getUser());
                $survey->setSchool($this->getUser()->getCurrentSchool());

                $foodSurveyDirectory = $this->getParameter('food_survey_directory'). \DIRECTORY_SEPARATOR ;
                $filename = $foodSurveyDirectory . $templateSurvey->getId() . '.jpg';
                $em->persist($survey);
                $em->flush($survey);

                $conn->commit();

                if (\is_dir($foodSurveyDirectory) && \is_file($filename)) {
                    $fileCopy = $foodSurveyDirectory . $survey->getId() . '.jpg';
                    \copy($filename, $fileCopy);

                    if (\file_exists($fileCopy)) {
                        $this->getSuccessMessage();
                    }
                }

                return $this->redirectToRoute('food_survey_creator', ['id' => $survey->getId()]);
            } catch (\Throwable $e) {
                $conn->rollBack();
                $this->getErrorMessage('Beim Speichern ist ein Fehler aufgetreten. ' . $e->getMessage());
                return $this->redirectToRoute('food_survey_home');
            }
        }

        return $this->render('food_survey/index/clone.html.twig', [
            'school' => $this->getUser()->getCurrentSchool(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/load/{id}", name="load", defaults={"id":null})
     * @param FoodSurvey|null $foodSurvey
     * @return JsonResponse
     */
    public function load(?FoodSurvey $foodSurvey): JsonResponse
    {
        if (\is_null($foodSurvey)) {
            throw $this->createNotFoundException('Objekt nicht gefunden!');
        }
        return new JsonResponse($foodSurvey);
    }

    /**
     * @Route("/upload", name="upload")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @throws ImagickException
     * @throws Exception
     */
    public function upload(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $id = $request->get('id');
        $name = $request->get('name', false);
        $data = $request->get('data', false);
        /** @var UploadedFile $image */
        $image = $request->files->get('image');

        if (empty($id) && (empty($name) || empty($image))) {
            throw $this->createNotFoundException('Name oder Bild nicht angegeben!');
        }
        $foodSurvey = empty($id) ? new FoodSurvey() : $entityManager->find(FoodSurvey::class, $id);
        if ($name !== false) {
            $foodSurvey->setName($name);
        }
        if ($data !== false) {
            $foodSurvey->setData($data);
        }
        $foodSurvey->setSchool($this->getUser()->getCurrentSchool());
        $foodSurvey->setCreatedBy($this->getUser());
        $entityManager->persist($foodSurvey);
        $entityManager->flush();

        $max_width = 2000;
        $max_height = 2000;

        if (! empty($image)) {
            if (! \is_dir($this->getParameter('filebase'))) {
                \mkdir($this->getParameter('filebase'));
            }
            if (! \is_dir($this->getParameter('food_survey_directory'))) {
                \mkdir($this->getParameter('food_survey_directory'));
            }

            $im = new Imagick($image->getRealPath());
            $im->autoOrient();
            $im->resizeImage(
                \min($im->getImageWidth(), $max_width),
                \min($im->getImageHeight(), $max_height),
                Imagick::FILTER_CATROM,
                1,
                true
            );
            $im->setFormat('jpeg');
            $im->writeImage($this->getParameter('food_survey_directory') . '/' . $foodSurvey->getId() . '.jpg');
        }
        return new JsonResponse($foodSurvey);
    }

    /**
     * @Route("/save-spot", name="save_spot")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @throws Exception
     */
    public function saveSpot(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $id = $request->get('id', null);
        $data = $request->get('data', null);
        $name = $request->get('name', false);
        $action = $request->get('action', false);
        $foodSurveyId = $request->get('foodSurveyId', null);

        $foodSurvey = $entityManager->find(FoodSurvey::class, $foodSurveyId);
        if (\is_null($foodSurvey)) {
            throw $this->createNotFoundException("Umfrage nicht gefunden!");
        }
        if ($foodSurvey->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw $this->createAccessDeniedException('Zugriff verweigert!');
        }
        if (\is_null($id)) {
            $foodSurveySpot = new FoodSurveySpot();
            $foodSurveySpot->setFoodSurvey($foodSurvey);
            $foodSurveySpot->setOrder($foodSurvey->getSpots()->count() + 1);
            $foodSurvey->getSpots()->add($foodSurveySpot);
        } else {
            $foodSurveySpot = $entityManager->find(FoodSurveySpot::class, $id);
            if (\is_null($foodSurveySpot)) {
                throw $this->createNotFoundException("Spot nicht gefunden!");
            }
        }
        if ($name !== false) {
            $foodSurveySpot->setName($name);
        }
        if (! \is_null($data)) {
            $foodSurveySpot->setData($data);
        }
        if ($action !== false) {
            if ($action === "up") {
                $foodSurvey->spotUp($foodSurveySpot);
            } elseif ($action === "down") {
                $foodSurvey->spotDown($foodSurveySpot);
            }
        }
        $entityManager->persist($foodSurveySpot);
        $entityManager->flush();
        $entityManager->refresh($foodSurvey);

        return new JsonResponse($foodSurvey);
    }

    /**
     * @Route("/result/{id}", name="result")
     * @param FoodSurvey $foodSurvey
     * @param MenuItem $menu
     * @return Response
     * @throws Exception
     */
    public function result(FoodSurvey $foodSurvey, MenuItem $menu): Response
    {
        if ($foodSurvey->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw new AccessDeniedException('Kita nicht gestattet.');
        }

        $menu['food-survey']->addChild($foodSurvey->getName() . ' Ergebnis', [
            'route' => 'food_survey_result',
            'routeParameters' => ['id' => $foodSurvey->getId()]
        ]);

        return $this->render('food_survey/index/result.html.twig', [
            'foodSurvey' => $foodSurvey,
            'school' => $this->getUser()->getCurrentSchool(),
        ]);
    }

    /**
     * @Route("/remove-spot", name="remove_spot")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @throws Exception
     */
    public function removeSpot(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $id = $request->get('id', null);

        $foodSurveySpot = $entityManager->find(FoodSurveySpot::class, $id);
        if (\is_null($foodSurveySpot)) {
            throw $this->createNotFoundException("Spot nicht gefunden!");
        }
        $foodSurvey = $foodSurveySpot->getFoodSurvey();
        if ($foodSurvey->getSchool() !== $this->getUser()->getCurrentSchool()) {
            throw $this->createAccessDeniedException('Zugriff verweigert!');
        }

        $entityManager->remove($foodSurveySpot);
        $entityManager->flush();
        $foodSurvey->reorderSpots();
        $entityManager->flush();

        return new JsonResponse($foodSurvey);
    }

    /**
     * @Route("/image/{id}/{spots}", name="image", defaults={"spots":false})
     * @param FoodSurvey $foodSurvey
     * @param bool $spots
     * @param bool $returnContent
     * @return string|void|null
     * @throws ImagickDrawException
     * @throws ImagickException
     */
    public function image(FoodSurvey $foodSurvey, bool $spots = false, bool $returnContent = false)
    {
        $file = $this->getParameter('food_survey_directory') . '/' . $foodSurvey->getId() . '.jpg';
        $image = new \Imagick($file);
        if ($spots) {
            foreach ($foodSurvey->getSpots() as $spot) {
                $draw = new \ImagickDraw();
                $draw->setStrokeOpacity(1);
                $draw->setStrokeColor("#FFFFFF");
                $draw->setStrokeWidth(10);
                $draw->setFillOpacity(0);
                $data = \json_decode($spot->getData(), true);
                $points = [];
                $xy = [];
                foreach ($data['attrs']['points'] as $i => $point) {
                    if ($i % 2) {
                        $xy['y'] = $point;
                        $points[] = $xy;
                        $xy = [];
                    } else {
                        $xy['x'] = $point;
                    }
                }
                foreach ($points as &$p) {
                    $p['x'] *= $data['attrs']['scaleX'] ?? 1;
                    $p['y'] *= $data['attrs']['scaleY'] ?? 1;
                    $angleRad = \deg2rad($data['attrs']['rotation'] ?? 0);
                    $cx = $data['attrs']['x'] ?? 0;
                    $cy = $data['attrs']['y'] ?? 0;
                    $s = \sin($angleRad);
                    $c = \cos($angleRad);
                    $newx = $p['x'] * $c - $p['y'] * $s;
                    $newy = $p['x'] * $s + $p['y'] * $c;
                    // translate point back:
                    $p['x'] = $newx + $cx;
                    $p['y'] = $newy + $cy;
                    //$p['x'] = $image->getImageWidth() - $p['x'];
                    //$p['y'] = $image->getImageHeight() - $p['y'];
                }
                if (\count($points) >= 3) {
                    $draw->polygon($points);
                    $image->drawImage($draw);
                }
                $fontDraw = new \ImagickDraw();
                $fontDraw->setStrokeColor('black');
                $fontDraw->setStrokeWidth(1);
                $fontDraw->setFillColor('white');
                //$fontDraw->setFont('Arial');
                $fontDraw->setFontSize(100);

                $fontX = $points[0]['x'] * ($data['attrs']['scaleX'] ?? 1);
                $fontY = $points[0]['y'] * ($data['attrs']['scaleY'] ?? 1);
                $image->annotateImage($fontDraw, $fontX, $fontY, 0, $spot->getOrder() . '.');
            }
        }
        if ($returnContent) {
            return $image->__toString();
        }
        \header("Content-Type: image/png");
        echo $image->getImageBlob();
    }

    /**
     * @Route("/export/{id}", name="export")
     * @param FoodSurvey $foodSurvey
     * @return void
     * @throws Exception
     */
    public function export(FoodSurvey $foodSurvey): void
    {
        $img = \base64_encode($this->image($foodSurvey, true, true));
        // instantiate and use the dompdf class
        $options = new Options();
        $options->setChroot('/var/www/public');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->render('pdf/food_survey.html.twig', [
            'currentSchool' => $this->getUser()->getCurrentSchool(),
            'foodSurvey' => $foodSurvey,
            'topic' => 'Teller-Check vom ' . $foodSurvey->getClosesAt()->format('d.m.Y'),
            'img' => $img
        ])->getContent());
        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();
        // Output the generated PDF to Browser
        $dompdf->stream();
    }
}
