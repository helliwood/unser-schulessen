<?php

namespace App\Tests\Controller\Survey;

use App\Entity\Survey\Category;
use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveyQuestion;
use App\Tests\Controller\AbstractTestController;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class IndexControllerTest extends AbstractTestController
{
    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testIndex()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Zufriedenheitsumfragen', $crawler->filter('h1')->text());
    }

    public function testNew()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neue Umfrage', $crawler->filter('h1')->text());

        $postData = ['survey' => [], 'questions' => []];
        $postData['survey']['name'] = "Umfrage";
        $postData['survey']['type'] = "voucher";
        $postData['survey']['numberOfVoucher'] = "10";
        $postData['survey']['closesAt'] = "2019-09-30T09:30:00.00Z";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/survey/new', $postData);
//        dd($this->client->getResponse()->getContent());
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/edit/' . $survey->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Umfrage bearbeiten', $crawler->filter('h1')->text());

        $postData = ['survey' => []];
        $postData['survey']['name'] = "Umfrage bearbeitet neu";
        $postData['survey']['type'] = "voucher";
        $postData['survey']['closesAt'] = "2030-09-30T09:30:00.00Z";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/survey/edit/' . $survey->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testQuestions()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/questions/' . $survey->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($survey->getName(), $crawler->filter('h1')->text());
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/questions/' . $survey->getId(), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNotEmpty($JSON_response);
    }

    public function testNewQuestion($question = "Frage 1", $type = SurveyQuestion::TYPE_HAPPY_UNHAPPY, $choices = [])
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy(['state' => '0'], ['createdAt' => 'DESC']);
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/questions/add/' . $survey->getId() . '/' . $type);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Frage hinzufügen', $crawler->filter('h1')->text());

        $postData = ['survey_question' => []];
        $postData['survey_question']['question'] = $question;
        if (\count($choices) > 0) {
            $postData['survey_question']['choices'] = [];
            foreach ($choices as $choice) {
                $postData['survey_question']['choices'][] = ["choice" => $choice];
            }
        }
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/survey/questions/add/' . $survey->getId() . '/' . $type, $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEditQuestion()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/questions/edit/' . $survey->getQuestions()->last()->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Frage bearbeiten', $crawler->filter('h1')->text());

        $postData = ['survey_question' => []];
        $postData['survey_question']['question'] = "Frage 1 neu";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/survey/questions/edit/' . $survey->getQuestions()->last()->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testQuestionUp()
    {
        $this->testNewQuestion("Frage 2", SurveyQuestion::TYPE_SINGLE, ["Antwort 1", "Antwort 2"]);
        $this->testNewQuestion("Frage 3", SurveyQuestion::TYPE_SINGLE, ["Antwort 1", "Antwort 2"]);
        $this->testNewQuestion("Frage 4", SurveyQuestion::TYPE_MULTI, ["Antwort 1", "Antwort 2"]);

        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var SurveyQuestion $question */
        $question = $this->getEntityManager()->getRepository(SurveyQuestion::class)->findOneBy(["question" => "Frage 2", "survey" => $survey]);

        $postData = ['action' => 'up', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/survey/questions/' . $survey->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var SurveyQuestion $question */
        $question = $this->getEntityManager()->getRepository(SurveyQuestion::class)->findOneBy(["question" => "Frage 2", "survey" => $survey]);
        $this->assertSame(1, $question->getOrder());
    }

    public function testDown()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var SurveyQuestion $question */
        $question = $this->getEntityManager()->getRepository(SurveyQuestion::class)->findOneBy(["question" => "Frage 2", "survey" => $survey]);

        $postData = ['action' => 'down', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/survey/questions/' . $survey->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var SurveyQuestion $question */
        $question = $this->getEntityManager()->getRepository(SurveyQuestion::class)->findOneBy(["question" => "Frage 2", "survey" => $survey]);
        $this->assertSame(2, $question->getOrder());
    }

    public function testDelete()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var SurveyQuestion $question */
        $question = $this->getEntityManager()->getRepository(SurveyQuestion::class)->findOneBy(["question" => "Frage 2", "survey" => $survey]);

        $postData = ['action' => 'delete_question', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/survey/questions/' . $survey->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var SurveyQuestion $question */
        $question = $this->getEntityManager()->getRepository(SurveyQuestion::class)->findOneBy(["question" => "Frage 2", "survey" => $survey]);
        $this->assertNull($question);
    }

    public function testList()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/list', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testListClosed()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/list-closed', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testVoucher()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/voucher/' . $survey->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Voucher', $crawler->filter('h1')->text());

        $postData = ['voucher' => []];
        $postData['voucher']['numberOfVoucher'] = 10;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/survey/voucher/' . $survey->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testVoucherAjax()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/voucher/' . $survey->getId(), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testVoucherDownload()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);
        ob_start();
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/voucher-download/' . $survey->getId());
        $getContent = ob_get_contents();
        ob_end_clean();
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testState()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/state/' . Survey::STATE_ACTIVE . '/' . $survey->getId());
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/state/' . Survey::STATE_CLOSED . '/' . $survey->getId());
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/state/' . Survey::STATE_ACTIVE . '/' . $survey->getId());
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testProposalCategories()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/proposal/categories', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testProposalQuestions()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy([], ['id' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/proposal/questions/' . $category->getId(), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testResult()
    {
        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var $school */
        $school = $survey->getSchool();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/survey/result/' . $survey->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($school . ': Umfrage-Ergebnis vom ' . $survey->getCreatedAt()->format("d.m.Y"), $crawler->filter('h1')->text());

    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCopySurvey()
    {
        $this->client->followRedirects();

//        $temp = $this->client->request('GET', '/survey/copy/99999');
//        var_dump($temp);
//        $this->expectException(InvalidArgumentException::class);


        /** @var Survey $survey */
        $survey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['state' => 'DESC']);
        if ($survey instanceof Survey) {

            /** @var Crawler $crawler */
            $crawler = $this->client->request('GET', '/survey/copy/' . $survey->getId());

            $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
            $clonedSurvey = $this->getEntityManager()->getRepository(Survey::class)->findOneBy([], ['id' => 'DESC']);
            $this->assertTrue($clonedSurvey->getCreatedBy() === $this->getUser());
            $this->assertTrue($clonedSurvey->getType() === $survey->getType());
            $this->assertTrue($clonedSurvey->getSchool()->getName() === $survey->getSchool()->getName());
            $this->assertTrue($clonedSurvey->getState() === 0);
            $this->assertTrue(count($survey->getQuestions()) === count($clonedSurvey->getQuestions()));

            $questionsIdentical = false;
            $choicesIdentical = false;
            if (! is_null($survey->getQuestions())) {
                foreach ($survey->getQuestions() as $question) {
                    $questionsIdentical = false;
                    $choicesIdentical = false;
                    foreach ($clonedSurvey->getQuestions() as $clonedQuestion) {
                        if ($question->getQuestion() == $clonedQuestion->getQuestion()) {
                            $questionsIdentical = true;
                        }
                        foreach ($question->getChoices() as $choice) {
                            foreach ($clonedQuestion->getChoices() as $clonedChoice) {
                                if ($choice->getChoice() == $clonedChoice->getChoice()) {
                                    $choicesIdentical = true;
                                    continue;
                                }
                            }
                        }
                    }

                }
            }
            $this->assertTrue(count($survey->getVouchers()) == count($clonedSurvey->getVouchers()));

            $this->assertTrue($questionsIdentical);
            $this->assertTrue($choicesIdentical);
        }
    }
}
