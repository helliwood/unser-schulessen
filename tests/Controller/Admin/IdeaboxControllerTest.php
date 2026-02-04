<?php

namespace App\Tests\Controller\Admin;

use App\Entity\QualityCheck\Ideabox;
use App\Entity\QualityCheck\Question;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class IdeaboxControllerTest extends AbstractTestController
{
    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testIndex()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/ideabox/' . $question->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($question->getQuestion() . ' Ideenbox', $crawler->filter('h1')->text());
    }

    public function testNew($idea = 'Idee Neu')
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/ideabox/new/' . $question->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neue Idee', $crawler->filter('h1')->text());

        $postData = ['ideabox' => []];
        $postData['ideabox']['idea'] = $idea;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/ideabox/new/' . $question->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => 'Idee Neu']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/ideabox/edit/' . $ideabox->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Idee bearbeiten', $crawler->filter('h1')->text());

        $postData = ['ideabox' => []];
        $postData['ideabox']['idea'] = 'Idee 1';
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/ideabox/edit/' . $ideabox->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testNewAgain()
    {
        $this->testNew('Idee 2');
    }

    public function testUp()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => 'Idee 2']);

        $postData = ['action' => 'up', 'ideabox_id' => $ideabox->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/ideabox/' . $question->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => 'Idee 2']);
        $this->assertSame(1, $ideabox->getOrder());
    }

    public function testDown()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => 'Idee 2']);

        $postData = ['action' => 'down', 'ideabox_id' => $ideabox->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/ideabox/' . $question->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => 'Idee 2']);
        $this->assertSame(2, $ideabox->getOrder());
    }

    public function testDelete()
    {
        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => 'Idee 2']);

        $postData = ['action' => 'delete_idea', 'ideabox_id' => $ideabox->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/category/questions/ideabox/' . $question->getId(), $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        /** @var Ideabox $ideabox */
        $ideabox = $this->getEntityManager()->getRepository(Ideabox::class)->findOneBy(['idea' => 'Idee 2']);
        $this->assertNull($ideabox);
    }
}
