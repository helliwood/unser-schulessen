<?php

namespace App\Tests\Controller\Admin\Survey;

use App\Entity\Survey\Category;
use App\Entity\Survey\Question;
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
        $crawler = $this->client->request('GET', '/admin/survey/questions/' . $category->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($category->getName() . ' Fragen', $crawler->filter('h1')->text());
    }

    public function testIndexAjax()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/questions/' . $category->getId(), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNew($name = 'Frage Neu')
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/questions/new/' . $category->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neue Frage', $crawler->filter('h1')->text());

        $postData = ['question' => []];
        $postData['question']['question'] = $name;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/questions/new/' . $category->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage Neu']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/questions/edit/' . $question->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Frage bearbeiten', $crawler->filter('h1')->text());

        $postData = ['question' => []];
        $postData['question']['question'] = 'Frage 1';
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/questions/edit/' . $question->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testNewAgain()
    {
        $this->testNew('Frage 2');
    }

    public function testUp()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);

        $postData = ['action' => 'up', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/questions/' . $question->getCategory()->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);
        $this->assertSame(1, $question->getOrder());
    }

    public function testDown()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);

        $postData = ['action' => 'down', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/questions/' . $question->getCategory()->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);
        $this->assertSame(2, $question->getOrder());
    }

    public function testDelete()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);

        $postData = ['action' => 'delete_question', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/questions/' . $question->getCategory()->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 2']);
        $this->assertNull($question);
    }
}
