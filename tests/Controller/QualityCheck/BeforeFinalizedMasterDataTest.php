<?php

namespace App\Tests\Controller\QualityCheck;

use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Result;
use App\Tests\Controller\AbstractTestController;
use PHPUnit\Runner\Exception;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class BeforeFinalizedMasterDataTest extends AbstractTestController
{
    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testBeforeFinalizedMasterData()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_check/');
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
