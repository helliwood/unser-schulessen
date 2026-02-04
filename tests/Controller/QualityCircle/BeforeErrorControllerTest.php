<?php


namespace App\Tests\Controller\QualityCircle;


use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class BeforeErrorControllerTest extends AbstractTestController
{
    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testBeforeError()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/new');
        // The test environment has proper test data, so before() doesn't redirect
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }
}