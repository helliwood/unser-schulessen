<?php

namespace App\Tests\Controller\Admin\Survey;

use App\Entity\Survey\Category;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class IndexControllerTest extends AbstractTestController
{
    protected $client = null;
    protected static $categoryNew;
    protected static $categoryEdit;
    protected static $categorySecond;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
        if (self::$categoryNew === null) {
            $suffix = uniqid('', true);
            self::$categoryNew = 'Kategorie Neu '.$suffix;
            self::$categoryEdit = 'Kategorie 1 '.$suffix;
            self::$categorySecond = 'Kategorie 2 '.$suffix;
        }
    }

    public function testIndex()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Umfragen - Kategorien', $crawler->filter('h1')->text());
    }

    public function testIndexAjax()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNew($name = null)
    {
        if ($name === null) {
            $name = self::$categoryNew;
        }

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neue Kategorie', $crawler->filter('h1')->text());

        $postData = ['category' => []];
        $postData['category']['name'] = $name;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categoryNew]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/edit/' . $category->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($category->getName(), $crawler->filter('h1')->text());

        $postData = ['category' => []];
        $postData['category']['name'] = self::$categoryEdit;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/edit/' . $category->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testNewAgain()
    {
        $this->testNew(self::$categorySecond);
    }

    public function testUp()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categorySecond]);
        $orderBeforeUp = $category->getOrder();

        $postData = ['action' => 'up', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categorySecond]);
        $this->assertLessThan($orderBeforeUp, $category->getOrder());
    }

    public function testDown()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categorySecond]);
        $orderBeforeDown = $category->getOrder();

        $postData = ['action' => 'down', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categorySecond]);
        $this->assertGreaterThan($orderBeforeDown, $category->getOrder());
    }

    public function testDelete()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categorySecond]);

        $postData = ['action' => 'delete_category', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => self::$categorySecond]);
        $this->assertNull($category);
    }
}
