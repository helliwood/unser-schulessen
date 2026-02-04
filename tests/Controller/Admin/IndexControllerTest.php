<?php

namespace App\Tests\Controller\Admin;

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
        $crawler = $this->client->request('GET', '/admin');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Administration', $crawler->filter('h1')->text());
    }

    public function testIndexWithSelectedYear()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/2025' );
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Administration', $crawler->filter('h1')->text());
    }


}
