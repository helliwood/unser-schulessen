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
    protected static $categoryName;
    protected static $questionNew;
    protected static $questionEdit;
    protected static $questionSecond;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
        if (self::$categoryName === null) {
            $suffix = uniqid('', true);
            self::$categoryName = 'Kategorie 1 '.$suffix;
            self::$questionNew = 'Frage Neu '.$suffix;
            self::$questionEdit = 'Frage 1 '.$suffix;
            self::$questionSecond = 'Frage 2 '.$suffix;
        }
    }

    public function testIndex()
    {
        $category = $this->getOrCreateCategory();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/questions/' . $category->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($category->getName() . ' Fragen', $crawler->filter('h1')->text());
    }

    public function testIndexAjax()
    {
        $category = $this->getOrCreateCategory();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/questions/' . $category->getId(), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNew($name = null)
    {
        if ($name === null) {
            $name = self::$questionNew;
        }

        $category = $this->getOrCreateCategory();

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
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionNew]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/questions/edit/' . $question->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Frage bearbeiten', $crawler->filter('h1')->text());

        $postData = ['question' => []];
        $postData['question']['question'] = self::$questionEdit;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/questions/edit/' . $question->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testNewAgain()
    {
        $this->testNew(self::$questionSecond);
    }

    public function testUp()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);
        $orderBeforeUp = $question->getOrder();

        $postData = ['action' => 'up', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/questions/' . $question->getCategory()->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);
        $this->assertLessThan($orderBeforeUp, $question->getOrder());
    }

    public function testDown()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);
        $orderBeforeDown = $question->getOrder();

        $postData = ['action' => 'down', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/questions/' . $question->getCategory()->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);
        $this->assertGreaterThan($orderBeforeDown, $question->getOrder());
    }

    public function testDelete()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);

        $postData = ['action' => 'delete_question', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/questions/' . $question->getCategory()->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);
        $this->assertNull($question);
    }

    private function getOrCreateCategory(): Category
    {
        $em = $this->getEntityManager();
        /** @var Category|null $category */
        $category = $em->getRepository(Category::class)->findOneBy(['name' => self::$categoryName]);
        if ($category !== null) {
            return $category;
        }

        $category = new Category();
        $category->setName(self::$categoryName);
        $category->setOrder(1);
        $em->persist($category);
        $em->flush();

        return $category;
    }
}
