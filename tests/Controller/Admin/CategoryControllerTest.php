<?php

namespace App\Tests\Controller\Admin;

use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Questionnaire;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class CategoryControllerTest extends AbstractTestController
{
    protected $client = null;
    protected const QUESTIONNAIRE_NEW = "Fragebogen Neu";
    protected const CATEGORY_NEW = "Kategorie neu";
    protected const CATEGORY_ONE = "Kategorie 1";
    protected const CATEGORY_TWO = "Kategorie 2";

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testNew($categoryName = self::CATEGORY_NEW)
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_NEW]);

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
        $postData['category']['name'] = 'Subcategory';
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/category/new/' . $questionnaire->getId() . '/' . $parentCat->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexAjax()
    {
        /** @var int $cat */
        $cat = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::CATEGORY_NEW]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/category/list/' . $cat->getId(), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testEdit()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::CATEGORY_NEW]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/category/edit/' . $category->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($category->getName(), $crawler->filter('h1')->text());

        $postData = ['category' => []];
        $postData['category']['name'] = self::CATEGORY_ONE;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/category/edit/' . $category->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testUp()
    {
        $this->testNew(self::CATEGORY_TWO);

        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_NEW]);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::CATEGORY_TWO]);

        $postData = ['action' => 'up', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/show/' . $questionnaire->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::CATEGORY_TWO]);
        $this->assertSame(1, $category->getOrder());
    }

    public function testDown()
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_NEW]);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::CATEGORY_TWO]);

        $postData = ['action' => 'down', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/show/' . $questionnaire->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::CATEGORY_TWO]);
        $this->assertSame(2, $category->getOrder());
    }

    public function testDelete()
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_NEW]);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::CATEGORY_TWO]);

        $postData = ['action' => 'delete_category', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/show/' . $questionnaire->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::CATEGORY_TWO]);
        $this->assertNull($category);
    }
}
