<?php

namespace App\Tests\Controller\QualityCircle;

use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Result;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class Index2ControllerTest extends AbstractTestController
{
    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    /**
     * Nochmal testen um die json_encode von ToDo zu erwischen
     */
    public function testIndexAjax()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }
}
