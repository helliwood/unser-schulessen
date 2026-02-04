<?php


namespace App\Tests\Controller\MasterData;


use App\Entity\Person;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class ContactsControllerTest extends AbstractTestController
{
    protected $client = null;
    protected $lastName = "Testorganisation Neu";

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testList()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/contacts/?page=1&size=10&sort=lastName&sortDesc=false', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNew()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/contacts/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neuer Kontakt', $crawler->filter('h1')->text());

        $postData = ['contact' => []];
        $postData['contact']['salutation'] = "Organisation";
        $postData['contact']['academicTitle'] = "";
        $postData['contact']['firstName'] = "";
        $postData['contact']['lastName'] = $this->lastName;
        $postData['contact']['email'] = "";
        $postData['contact']['telephone'] = "";
        $postData['contact']['note'] = "";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/contacts/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData['cancel'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/contacts/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        $contact = $this->getEntityManager()->getRepository(Person::class)->findOneBy(["lastName" => $this->lastName]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/contacts/edit/' . $contact->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($this->lastName, $crawler->filter('h1')->text());

        $postData = ['contact' => []];
        $postData['contact']['salutation'] = "Herr";
        $postData['contact']['academicTitle'] = "Dr.";
        $postData['contact']['firstName'] = "Dieter";
        $postData['contact']['lastName'] = $this->lastName;
        $postData['contact']['email'] = "dieter@test.test";
        $postData['contact']['telephone'] = "030123456";
        $postData['contact']['note'] = "Dies ist ein Test!";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/contacts/edit/' . $contact->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData['cancel'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/contacts/edit/' . $contact->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShow()
    {
        $contact = $this->getEntityManager()->getRepository(Person::class)->findOneBy(["lastName" => $this->lastName]);
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/contacts/show/' . $contact->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $postData['close'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/contacts/show/' . $contact->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
