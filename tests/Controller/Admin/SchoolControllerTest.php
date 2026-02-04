<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Address;
use App\Entity\School;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class SchoolControllerTest extends AbstractTestController
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
        $crawler = $this->client->request('GET', '/admin/school/');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Schulen', $crawler->filter('h1')->text());
    }

    public function testIndexAjax()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/school/list', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNew()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/school/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neue Schule', $crawler->filter('h1')->text());

        $postData = ['school' => ['address' => []]];
        $postData['school']['name'] = "Testschule";
        $postData['school']['schoolNumber'] = "321";
        $postData['school']['headmaster'] = "Herr Dr. Würtschen";
        $postData['school']['phoneNumber'] = "0301324564789";
        $postData['school']['faxNumber'] = "030123456789";
        $postData['school']['emailAddress'] = "test@testschule.de";
        $postData['school']['webpage'] = "www.testschule.de";
        $postData['school']['educationAuthority'] = "keine!";
        $postData['school']['schoolType'] = "Hauptschule";
        $postData['school']['schoolOperator'] = "Herr Wurst";
        $postData['school']['particularity'] = "";

        if(isset($_ENV['APP_STATE_COUNTRY']) && $_ENV['APP_STATE_COUNTRY'] === 'rp') {
            $dt = new \DateTime();
            $dt->modify('+1 year');
            $postData['school']['auditEnd'] = $dt->format('Y-m-d');
        }

        $postData['school']['address']['street'] = "Hauptstrasse 2";
        $postData['school']['address']['postalcode'] = "12345";
        $postData['school']['address']['city'] = "Berlin";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShow()
    {
        /** @var School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => 'Testschule']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/school/show/' . $school->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Testschule', $crawler->filter('h1')->text());
    }

    public function testShowAjax()
    {
        /** @var School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => 'Testschule']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/school/show/' . $school->getId(), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testEdit()
    {
        /** @var School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => 'Testschule']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/school/edit/' . $school->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Testschule', $crawler->filter('h1')->text());

        $postData = ['school' => ['address' => []]];
        $postData['school']['name'] = "Testschule Yepp";
        $postData['school']['schoolNumber'] = "222";
        $postData['school']['headmaster'] = "";
        $postData['school']['phoneNumber'] = "";
        $postData['school']['faxNumber'] = "";
        $postData['school']['emailAddress'] = "";
        $postData['school']['webpage'] = "";
        $postData['school']['educationAuthority'] = "";
        $postData['school']['schoolType'] = "";
        $postData['school']['schoolOperator'] = "";
        $postData['school']['particularity'] = "";

        if(isset($_ENV['APP_STATE_COUNTRY']) && $_ENV['APP_STATE_COUNTRY'] === 'rp') {
            $dt = new \DateTime();
            $dt->modify('+1 year');
            $postData['school']['auditEnd'] = $dt->format('Y-m-d');
        }

        $postData['school']['address']['street'] = "";
        $postData['school']['address']['postalcode'] = "";
        $postData['school']['address']['city'] = "Berlin";
        if(isset($_ENV['APP_STATE_COUNTRY']) && $_ENV['APP_STATE_COUNTRY'] === 'by') {
            $postData['school']['address']['district'] = "OBay.";
        }
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/edit/' . $school->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
