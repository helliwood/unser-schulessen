<?php

namespace App\Tests\Controller\Admin\Survey;

use App\Entity\Survey\Category;
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

    public function testNew($name = 'Kategorie Neu')
    {
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
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie Neu']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/survey/edit/' . $category->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($category->getName(), $crawler->filter('h1')->text());

        $postData = ['category' => []];
        $postData['category']['name'] = "Kategorie 1";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/edit/' . $category->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testNewAgain()
    {
        $this->testNew('Kategorie 2');
    }

    public function testUp()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 2']);

        $postData = ['action' => 'up', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 2']);
        $this->assertSame(1, $category->getOrder());
    }

    public function testDown()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 2']);

        $postData = ['action' => 'down', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 2']);
        $this->assertSame(2, $category->getOrder());
    }

    public function testDelete()
    {
        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 2']);

        $postData = ['action' => 'delete_category', 'category_id' => $category->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/survey/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Category $category */
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['name' => 'Kategorie 2']);
        $this->assertNull($category);
    }
}
