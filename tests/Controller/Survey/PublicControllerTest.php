<?php

namespace App\Tests\Controller\Survey;

use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveyQuestion;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class PublicControllerTest extends AbstractTestController
{
    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testShow()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy(['state' => Survey::STATE_ACTIVE], ['createdAt' => 'DESC']);
        // Enddatum wird durch den stateTest im IndexController manipuliert.
        $survey->setClosesAt(new \DateTime('2030-09-10'));
        $this->getEntityManager()->persist($survey);
        $this->getEntityManager()->flush();
        // block end

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/Umfrage/' . $survey->getUuid());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($survey->getName(), $crawler->filter('h1')->text());

        $postData = ['survey' => []];
        foreach ($survey->getQuestions() as $question) {
            if ($question->getType() === SurveyQuestion::TYPE_HAPPY_UNHAPPY) {
                $postData['survey'][$question->getId()] = "1";
            } elseif ($question->getType() === SurveyQuestion::TYPE_SINGLE) {
                $postData['survey'][$question->getId()] = $question->getChoices()->first()->getId();
            } elseif ($question->getType() === SurveyQuestion::TYPE_MULTI) {
                $postData['survey'][$question->getId()][] = $question->getChoices()->first()->getId();
            }
        }
        $postData['survey']['voucher'] = $survey->getVouchers()->first()->getVoucher();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/Umfrage/' . $survey->getUuid(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
