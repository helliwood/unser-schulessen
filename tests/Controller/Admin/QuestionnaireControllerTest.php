<?php

namespace App\Tests\Controller\Admin;

use App\Entity\QualityCheck\Questionnaire;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class QuestionnaireControllerTest extends AbstractTestController
{
    protected $client = null;
    protected const QUESTIONNAIRE_NEW = 'Fragebogen Neu';

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testIndex()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Fragebögen', $crawler->filter('h1')->text());
    }

    public function testIndexAjax()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNew()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neuer Fragebogen', $crawler->filter('h1')->text());

        $postData = ['questionnaire' => []];
        $postData['questionnaire']['name'] = self::QUESTIONNAIRE_NEW;
        $postData['questionnaire']['basedOn'] = "";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_NEW]);

        $postData = ['action' => 'delete_questionnaire', 'questionnaire_id' => $questionnaire->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNewAgain()
    {
        $this->testNew();
    }

    public function testShow()
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_NEW]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/show/' . $questionnaire->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($questionnaire->getName(), $crawler->filter('h1')->text());
    }

    public function testShow4Ajax()
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_NEW]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/show/' . $questionnaire->getId(), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }
}
