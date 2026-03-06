<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\UnitTestFixtures;
use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Ideabox;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use App\Entity\User;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class IdeaboxControllerTest extends AbstractTestController
{
    protected $client = null;
    protected static $ideaNew;
    protected static $ideaEdit;
    protected static $ideaSecond;
    protected static $questionName;
    protected static $questionnaireName;
    protected static $categoryName;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
        if (self::$ideaNew === null) {
            $suffix = uniqid('', true);
            self::$ideaNew = 'Idee Neu '.$suffix;
            self::$ideaEdit = 'Idee 1 '.$suffix;
            self::$ideaSecond = 'Idee 2 '.$suffix;
            self::$questionName = 'Frage 1 '.$suffix;
            self::$questionnaireName = 'Fragebogen Neu '.$suffix;
            self::$categoryName = 'Kategorie 1 '.$suffix;
        }
    }

    public function testIndex()
    {
        $question = $this->getOrCreateQuestion();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/ideabox/' . $question->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($question->getQuestion() . ' Ideenbox', $crawler->filter('h1')->text());
    }

    public function testNew($idea = null)
    {
        if ($idea === null) {
            $idea = self::$ideaNew;
        }

        $question = $this->getOrCreateQuestion();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/ideabox/new/' . $question->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neue Idee', $crawler->filter('h1')->text());

        $postData = ['ideabox' => []];
        $postData['ideabox']['idea'] = $idea;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/ideabox/new/' . $question->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => self::$ideaNew]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/ideabox/edit/' . $ideabox->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Idee bearbeiten', $crawler->filter('h1')->text());

        $postData = ['ideabox' => []];
        $postData['ideabox']['idea'] = self::$ideaEdit;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/ideabox/edit/' . $ideabox->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testNewAgain()
    {
        $this->testNew(self::$ideaSecond);
    }

    public function testUp()
    {
        $question = $this->getOrCreateQuestion();

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => self::$ideaSecond]);
        $orderBeforeUp = $ideabox->getOrder();

        $postData = ['action' => 'up', 'ideabox_id' => $ideabox->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/ideabox/' . $question->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => self::$ideaSecond]);
        $this->assertLessThan($orderBeforeUp, $ideabox->getOrder());
    }

    public function testDown()
    {
        $question = $this->getOrCreateQuestion();

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => self::$ideaSecond]);
        $orderBeforeDown = $ideabox->getOrder();

        $postData = ['action' => 'down', 'ideabox_id' => $ideabox->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/ideabox/' . $question->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => self::$ideaSecond]);
        $this->assertGreaterThan($orderBeforeDown, $ideabox->getOrder());
    }

    public function testDelete()
    {
        $question = $this->getOrCreateQuestion();

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => self::$ideaSecond]);

        $postData = ['action' => 'delete_idea', 'ideabox_id' => $ideabox->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/ideabox/' . $question->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => self::$ideaSecond]);
        $this->assertNull($ideabox);
    }

    private function getOrCreateQuestion(): Question
    {
        $em = $this->getEntityManager();
        /** @var Question|null $question */
        $question = $em->getRepository(Question::class)->findOneBy(['question' => self::$questionName]);
        if ($question !== null) {
            return $question;
        }

        /** @var Category|null $category */
        $category = $em->getRepository(Category::class)->findOneBy(['name' => self::$categoryName]);
        if ($category === null) {
            /** @var Questionnaire|null $questionnaire */
            $questionnaire = $em->getRepository(Questionnaire::class)->findOneBy(['name' => self::$questionnaireName]);
            if ($questionnaire === null) {
                /** @var User $createdBy */
                $createdBy = $em->getRepository(User::class)->findOneBy(['email' => UnitTestFixtures::TESTUSER_EMAIL]);
                $questionnaire = new Questionnaire();
                $questionnaire->setName(self::$questionnaireName);
                $questionnaire->setCreatedBy($createdBy);
                $em->persist($questionnaire);
                $em->flush();
            }

            $category = new Category();
            $category->setName(self::$categoryName);
            $category->setOrder(1);
            $category->setQuestionnaire($questionnaire);
            $em->persist($category);
            $em->flush();
        }

        $question = new Question();
        $question->setCategory($category);
        $question->setQuestion(self::$questionName);
        $question->setOrder(1);
        $question->setType(Question::TYPE_NOT_NEEDED);
        $question->setSustainable(true);
        $question->setMiniCheck(true);
        $question->setMiniCheckInfo('miniCheckInfo');
        $em->persist($question);
        $em->flush();

        return $question;
    }
}
