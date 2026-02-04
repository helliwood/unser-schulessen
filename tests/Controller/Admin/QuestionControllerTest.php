<?php

namespace App\Tests\Controller\Admin;

use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Question;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class QuestionControllerTest extends AbstractTestController
{
    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testIndex()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/' . $category->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($category->getName() . ' Fragen', $crawler->filter('h1')->text());
    }

    public function testNew($question = 'Frage Neu', $withFormula = true)
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/new/' . $category->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neue Frage', $crawler->filter('h1')->text());

        $postData = ['question' => ['formula' => []]];
        $postData['question']['question'] = $question;
        $postData['question']['sustainable'] = true;
        $postData['question']['miniCheck'] = true;
        $postData['question']['miniCheckInfo'] = 'miniCheckInfo';
        $postData['question']['type'] = $withFormula ? "needed" : "not_needed";
        $postData['question']['formula']['formula_true'] = $withFormula ? "> 3" : "";
        $postData['question']['formula']['formula_false'] = $withFormula ? "<= 2" : "";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/new/' . $category->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage Neu']);
        $this->assertTrue($question->isFlagEqual('sustainable', true));

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/edit/' . $question->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($question->getQuestion(), $crawler->filter('h1')->text());

        $questionaireState = $question->getCategory()->getQuestionnaire()->getState();
        $postData = ['question' => ['formula' => []]];
        $postData['question']['question'] = 'Frage 1';
        $postData['question']['sustainable'] = true;

        if($questionaireState === 0) {
            $postData['question']['type'] = "not_needed";
            $postData['question']['formula']['formula_true'] = "";
            $postData['question']['formula']['formula_false'] = "";
        }
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/edit/' . $question->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEditWithFormula()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/edit/' . $question->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($question->getQuestion(), $crawler->filter('h1')->text());

        $postData = ['question' => ['formula' => []]];
        $postData['question']['question'] = 'Frage 1';
        $postData['question']['type'] = "needed";
        $postData['question']['formula']['formula_true'] = "> 3";
        $postData['question']['formula']['formula_false'] = "<= 2";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/edit/' . $question->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testNewAgain()
    {
        $this->testNew('Frage 2', false);
    }

    public function testUp()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 1']);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);

        $postData = ['action' => 'up', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/' . $category->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);
        $this->assertSame(1, $question->getOrder());
    }

    public function testDown()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 1']);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);

        $postData = ['action' => 'down', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/' . $category->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);
        $this->assertSame(2, $question->getOrder());
    }

    public function testDelete()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 1']);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);

        $postData = ['action' => 'delete_question', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/' . $category->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);
        $this->assertNull($question);
    }
}
