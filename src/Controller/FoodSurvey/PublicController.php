<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2022-10-19
 * Time: 15:04
 */

namespace App\Controller\FoodSurvey;

use App\Controller\AbstractController;
use App\Entity\FoodSurvey\FoodSurvey;
use App\Entity\FoodSurvey\FoodSurveyResult;
use App\Entity\FoodSurvey\FoodSurveySpotAnswer;
use Doctrine\ORM\EntityManagerInterface;
use ImagickDrawException;
use ImagickException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/Essensumfrage", name="food_survey_public_")
 */
class PublicController extends AbstractController
{
    /**
     * @Route("/{uuid}", name="show")
     * @param FoodSurvey $foodSurvey
     * @return Response
     */
    public function show(FoodSurvey $foodSurvey): Response
    {
        return $this->render('food_survey/public/show.html.twig', [
            'foodSurvey' => $foodSurvey
        ]);
    }

    /**
     * @Route("/save-result/{uuid}", name="save_result")
     * @param FoodSurvey             $foodSurvey
     * @param Request                $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function saveResult(FoodSurvey $foodSurvey, Request $request, EntityManagerInterface $entityManager): Response
    {
        $foodSurveyResult = new FoodSurveyResult();
        $foodSurveyResult->setFoodSurvey($foodSurvey);
        $foodSurveyResult->setUserAgent($request->headers->get('User-Agent'));
        $foodSurveyResult->setUserIp($request->getClientIp());
        $entityManager->persist($foodSurveyResult);
        foreach ($request->request->all() as $key => $value) {
            $foodSurveySpotAnswer = new FoodSurveySpotAnswer();
            $foodSurveySpotAnswer->setFoodSurveyResult($foodSurveyResult);
            $foodSurveySpotAnswer->setFoodSurveySpot($foodSurvey->getSpots()->get($key - 1));
            $foodSurveySpotAnswer->setAnswer((int)$value);
            $entityManager->persist($foodSurveySpotAnswer);
        }
        $entityManager->flush();
        return new JsonResponse($foodSurvey);
    }

    /**
     * @Route("/image/{id}", name="image")
     * @param FoodSurvey $foodSurvey
     * @return BinaryFileResponse
     * @throws ImagickDrawException
     * @throws ImagickException
     */
    public function image(FoodSurvey $foodSurvey): Response
    {
        $file = $this->getParameter('food_survey_directory') . '/' . $foodSurvey->getId() . '.jpg';
        if (! \file_exists($file)) {
            throw $this->createNotFoundException('Das Bild wurde nicht gefunden!');
        }

        $image = new \Imagick($file);
        /*
        foreach ($foodSurvey->getSpots() as $spot) {
            $draw = new \ImagickDraw();
            $draw->setStrokeOpacity(1);
            $draw->setStrokeColor("#FFFFFF");
            $draw->setStrokeWidth(10);
            $draw->setFillOpacity(0);
            $data = json_decode($spot->getData(), true);
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
                $angleRad = deg2rad($data['attrs']['rotation'] ?? 0);
                $cx = $data['attrs']['x'] ?? 0;
                $cy = $data['attrs']['y'] ?? 0;
                $s = sin($angleRad);
                $c = cos($angleRad);
                $newx = $p['x'] * $c - $p['y'] * $s;
                $newy = $p['x'] * $s + $p['y'] * $c;
                // translate point back:
                $p['x'] = $newx + $cx;
                $p['y'] = $newy + $cy;

                $p['x'] = $image->getImageWidth() - $p['x'];
                $p['y'] = $image->getImageHeight() - $p['y'];
            }
            $draw->polygon($points);
            $image->drawImage($draw);
        }
*/
        return new Response($image->getImageBlob(), Response::HTTP_OK, ["Content-Type" => "image/png"]);
    }
}
