<?php

namespace App\Tests\Controller\QualityCheck;

use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Result;
use App\Entity\School;
use App\Tests\Controller\AbstractTestController;
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
        $crawler = $this->client->request('GET', '/quality_check/');
//        dump($this->client->getResponse());die;
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Qualitäts-Check', $crawler->filter('h1')->text());
    }

    public function testIndexAjax()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testSkip()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/skip/1', ['hideModal' => 1]);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/edit');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Qualitäts-Check', $crawler->filter('h1')->text());

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/edit/9999');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/edit/0');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $postData['Questionnaire'][$question->getId()] = "4";
        $postData['next'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/quality_check/edit', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        unset($postData);
        $postData['Questionnaire'][$question->getId()] = "4";
        $postData['back'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/quality_check/edit', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        unset($postData);
        $postData['Questionnaire'][$question->getId()] = "4";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/quality_check/edit', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testQuestionTrue()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/check/' . $question->getId() . '/4', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame("true", $JSON_response);
    }

    public function testQuestionPartial()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/check/' . $question->getId() . '/3', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame("partial", $JSON_response);
    }

    public function testQuestionFalse()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/check/' . $question->getId() . '/2', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame("false", $JSON_response);
    }

    public function testQuestionNotAnswered()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/check/' . $question->getId() . '/1', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame("false", $JSON_response);
    }

    public function testFinalise()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/edit');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Qualitäts-Check', $crawler->filter('h1')->text());

        $postData['Questionnaire'][$question->getId()] = "4";
        $postData['finalise'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/quality_check/edit', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testResult()
    {
        /** @var Result $result */
        $result = $this->getEntityManager()->getRepository(Result::class)->findOneBy([], ['finalisedAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/result/' . $result->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** @var  School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => 'Testschule Neu']);
        $this->assertStringContainsString('Qualitäts-Check', $crawler->filter('h1')->text());
    }

    /**
     *  Test muesste eigentlich einen neuen User anlegen, um sicher zu stellen, dass dieser noch keinen Quality Check angelegt hat!
     */
    public function testWithoutResult()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/result');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** @var Result $result */
        $result = $this->getEntityManager()->getRepository(Result::class)->findOneBy([], ['finalisedAt' => 'DESC']);

        if (! $result) {
            $crawler = $this->client->followRedirect();
            $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        }
    }

    public function testWithBadResult()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/result/99999');
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testCloneResult()
    {
        $lastResult = $this->getEntityManager()->getRepository(Result::class)->findOneBy([], ['id' => 'DESC']);

        $finalisedResult = $this->getEntityManager()->getRepository(Result::class)->findOneBy(['finalised' => 'true'], ['id' => 'DESC']);

        if ($lastResult instanceof Result && $finalisedResult instanceof Result) {

            $this->assertTrue($lastResult === $finalisedResult, "Es liegt ein nicht finalisierter Test vor!");

            /** Nötig, da das Kopieren erst nach dem Redirect erfolgt! */
            $this->client->followRedirects();

            /** @var Crawler $crawler */
            $crawler = $this->client->request('GET', '/quality_check/copy/');

            $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

            $answersIdentical = false;
            foreach ($lastResult->getAnswers()->getValues() as $answer) {
                $answersIdentical = false;

                $clonedResult = $this->getEntityManager()->getRepository(Result::class)->findOneBy(['finalised' => 'false'], []);

                foreach ($clonedResult->getAnswers()->getValues() as $clonedAnswer) {
                    $answersIdentical = false;
                    if (
                        $clonedAnswer->getQuestion()->getId() === $answer->getQuestion()->getId()
                        && $clonedAnswer->getAnswer() === $answer->getAnswer()
                    ) {
                        $answersIdentical = true;
                        continue;
                    }
                }
            }

            $this->assertTrue($answersIdentical);

        } else {

            $this->assertFalse(\is_null($lastResult), 'Es liegt kein finalisierter Qualitäts-Check vor!');

        }
    }

//    public function testCloneResultFail()
//    {
//        $rr = $this->getEntityManager()->getRepository(Result::class)->findOneBy([], ['id' => 'DESC']);
//        if ($rr instanceof Result && $rr->isFinalised()) {
//            /** Nötig, da das Kopieren erst nach dem Redirect erfolgt!*/
//            $this->client->followRedirects();
//
//            /** @var Crawler $crawler */
//            $crawler = $this->client->request('GET', '/quality_check/copy/');
//
//            $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $this->client->getResponse()->getStatusCode());
//
//        } else {
//
//            $this->assertFalse(\is_null($rr), 'Es liegt kein finalisierter Qualitäts-Check vor!');
//
//        }
//    }
//    public function testExportWithResult()
//    {
//        /** @var Result $result */
//        $result = $this->getEntityManager()->getRepository(Result::class)->findOneBy([], ['finalisedAt' => 'DESC']);
//        ob_start();
//
//        /** @var Crawler $crawler */
//        $crawler = $this->client->request('GET', '/quality_check/export/' . $result->getId());
//
//        $getContent = ob_get_contents();
//        ob_end_clean();
//
//        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
//    }
//
//    const TESTSCHULE = "NoMasterDataTestschule";
//    public function testWithoutMasterData(){
//        $postData = ['school' => ['address' => []]];
//        $postData['school']['name'] = self::TESTSCHULE;
//        $postData['school']['address']['city'] = "Berlin";
//        $postData['save'] = "";
//
//        /** @var Crawler $crawler */
//        $crawler = $this->client->request('POST', '/admin/school/new', $postData);
//        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
//        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => self::TESTSCHULE]);
//
//        $postData = ['user_has_school' => []];
//        $postData['user_has_school']['email'] = 'noMasterData@helliwood.de';
//        $postData['user_has_school']['personType'] = 'Mitglied Mensa AG';
//        $postData['user_has_school']['sendInvitation'] = 0;
//        $postData['user_has_school']['role'] = "ROLE_ADMINISTRATIVE_MEMBER";
//        $postData['save'] = "";
//        /** @var Crawler $crawler */
//        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/new', $postData);
////        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
//
//
//        /** @var Crawler $crawler */
//        $crawler = $this->client->request('POST', '/');
//        dump($crawler); die;
//        $crawler->selectLink('<i class="fas fa-plus mr-1"></i>Annehmen')->link();
//
//
//        $crawler->selectLink('<i class="fas fa-exchange-alt mr-1"></i>Wechseln')->link();
//
//        /** @var Crawler $crawler */
//        $crawler = $this->client->request('POST', '/quality_check/edit/0');
//        $this->assertContains(
//            'Sie müssen erst die Stammdaten ausfüllen.',
//            $this->client->getResponse()->getContent()
//        );
//    }
//
//
//    public function testExportWithoutResult()
//    {
//        /** @var Crawler $crawler */
//        $crawler = $this->client->request('GET', '/quality_check/export');
////        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
//        $this->assertResponseStatusCode(self::STATUS_OK);
//        $this->assertResponseHeaderContains('Content-Type', 'application/pdf');
//    }
}
