<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\UnitTestFixtures;
use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use App\Entity\User;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class QuestionControllerTest extends AbstractTestController
{
    protected $client = null;
    protected static $questionNew;
    protected static $questionEdit;
    protected static $questionSecond;
    protected static $questionnaireName;
    protected static $categoryName;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
        if (self::$questionNew === null) {
            $suffix = uniqid('', true);
            self::$questionNew = 'Frage Neu '.$suffix;
            self::$questionEdit = 'Frage 1 '.$suffix;
            self::$questionSecond = 'Frage 2 '.$suffix;
            self::$questionnaireName = 'Fragebogen Neu '.$suffix;
            self::$categoryName = 'Kategorie 1 '.$suffix;
        }
    }

    public function testIndex()
    {
        $category = $this->getOrCreateCategory();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/' . $category->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($category->getName() . ' Fragen', $crawler->filter('h1')->text());
    }

    public function testNew($question = null, $withFormula = true)
    {
        if ($question === null) {
            $question = self::$questionNew;
        }

        $category = $this->getOrCreateCategory();

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
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionNew]);
        $this->assertTrue($question->isFlagEqual('sustainable', true));

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/edit/' . $question->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($question->getQuestion(), $crawler->filter('h1')->text());

        $questionaireState = $question->getCategory()->getQuestionnaire()->getState();
        $form = $crawler->selectButton('save')->form();
        $form['question']['question']->setValue(self::$questionEdit);

        if ($questionaireState === 0) {
            $form['question']['type']->setValue("not_needed");
            $form['question']['formula']['formula_true']->setValue("");
            $form['question']['formula']['formula_false']->setValue("");
        }

        /** @var Crawler $crawler */
        $crawler = $this->client->submit($form);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEditWithFormula()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionEdit]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/edit/' . $question->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($question->getQuestion(), $crawler->filter('h1')->text());

        $form = $crawler->selectButton('save')->form();
        $form['question']['question']->setValue(self::$questionEdit);
        $form['question']['type']->setValue("needed");
        $form['question']['formula']['formula_true']->setValue("> 3");
        $form['question']['formula']['formula_false']->setValue("<= 2");

        /** @var Crawler $crawler */
        $crawler = $this->client->submit($form);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testNewAgain()
    {
        $this->testNew(self::$questionSecond, false);
    }

    public function testUp()
    {
        $category = $this->getOrCreateCategory();

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);
        $orderBeforeUp = $question->getOrder();

        $postData = ['action' => 'up', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/' . $category->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);
        $this->assertLessThan($orderBeforeUp, $question->getOrder());
    }

    public function testDown()
    {
        $category = $this->getOrCreateCategory();

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);
        $orderBeforeDown = $question->getOrder();

        $postData = ['action' => 'down', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/' . $category->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);
        $this->assertGreaterThan($orderBeforeDown, $question->getOrder());
    }

    public function testDelete()
    {
        $category = $this->getOrCreateCategory();

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => self::$questionSecond]);

        $postData = ['action' => 'delete_question', 'question_id' => $question->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/' . $category->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
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

        return $category;
    }
}
