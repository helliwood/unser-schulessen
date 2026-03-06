<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\UnitTestFixtures;
use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Questionnaire;
use App\Entity\User;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class CategoryControllerTest extends AbstractTestController
{
    protected $client = null;
    protected static $questionnaireName;
    protected static $categoryNew;
    protected static $categoryOne;
    protected static $categoryTwo;
    protected static $subcategory;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
        if (self::$questionnaireName === null) {
            self::$questionnaireName = 'Fragebogen Neu '.uniqid('', true);
        }
        if (self::$categoryNew === null) {
            $suffix = uniqid('', true);
            self::$categoryNew = 'Kategorie neu '.$suffix;
            self::$categoryOne = 'Kategorie 1 '.$suffix;
            self::$categoryTwo = 'Kategorie 2 '.$suffix;
            self::$subcategory = 'Subcategory '.$suffix;
        }
    }

    public function testNew($categoryName = null)
    {
        if ($categoryName === null) {
            $categoryName = self::$categoryNew;
        }

        $questionnaire = $this->getOrCreateQuestionnaire();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/category/new/' . $questionnaire->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neue Kategorie', $crawler->filter('h1')->text());

        $postData = ['category' => []];
        $postData['category']['name'] = $categoryName;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/category/new/' . $questionnaire->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

//      test Subcategory
        /** @var int $parentCat */
        $parentCat = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => $categoryName]);
        /** @var $postData */
        $postData = ['category' => []];
        $postData['category']['name'] = self::$subcategory;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/category/new/' . $questionnaire->getId() . '/' . $parentCat->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexAjax()
    {
        /** @var int $cat */
        $cat = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categoryNew]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/category/list/' . $cat->getId(), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testEdit()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categoryNew]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/category/edit/' . $category->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($category->getName(), $crawler->filter('h1')->text());

        $postData = ['category' => []];
        $postData['category']['name'] = self::$categoryOne;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/category/edit/' . $category->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testUp()
    {
        $this->testNew(self::$categoryTwo);

        $questionnaire = $this->getOrCreateQuestionnaire();

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categoryTwo]);
        $orderBeforeUp = $category->getOrder();

        $postData = ['action' => 'up', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/show/' . $questionnaire->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categoryTwo]);
        $this->assertLessThan($orderBeforeUp, $category->getOrder());
    }

    public function testDown()
    {
        $questionnaire = $this->getOrCreateQuestionnaire();

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categoryTwo]);
        $orderBeforeDown = $category->getOrder();

        $postData = ['action' => 'down', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/show/' . $questionnaire->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categoryTwo]);
        $this->assertGreaterThan($orderBeforeDown, $category->getOrder());
    }

    public function testDelete()
    {
        $questionnaire = $this->getOrCreateQuestionnaire();

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categoryTwo]);

        $postData = ['action' => 'delete_category', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/show/' . $questionnaire->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categoryTwo]);
        $this->assertNull($category);
    }

    private function getOrCreateQuestionnaire(): Questionnaire
    {
        $em = $this->getEntityManager();
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

        return $questionnaire;
    }
}
