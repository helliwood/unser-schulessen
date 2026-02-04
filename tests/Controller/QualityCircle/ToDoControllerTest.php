<?php

namespace App\Tests\Controller\QualityCircle;

use App\Entity\QualityCheck\Answer;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCircle\ActionPlanNew;
use App\Entity\QualityCircle\ToDo;
use App\Entity\QualityCircle\ToDoNew;
use App\Entity\School;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class ToDoControllerTest extends AbstractTestController
{
    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testNew()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neues To Do', $crawler->filter('h1')->text());

        /** @var Question $question */
        $question = $this->getEntityManager()->getRepository(Question::class)->findOneBy(['question' => 'Frage 1']);

        /** @var Answer $answer */
        $answer = $this->getEntityManager()->getRepository(Answer::class)->findOneBy(['question' => $question]);

        $postData['answers'] = [];
        $postData['answers'][] = $answer->getId();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/quality_circle/todo/new', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->request('POST', '/quality_circle/todo/new', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
    }

    public function testNewAjax()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/new?answer=1', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testEdit()
    {
        /** @var ToDoNew $todo */
        $todo = $this->getEntityManager()->getRepository(ToDoNew::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/edit/' . $todo->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('To Do vom ' . $todo->getCreatedAt()->format('d.m.Y'), $crawler->filter('h1')->text());

    }

//    public function testIdeaPoolAjax()
//    {
//        // ToDo werden die Ideen zurück gegeben?
//    }
//
//    public function testCompleteToDoList()
//    {
//        // Todo
//    }

//    public function testDownload()
//    {
//        // ToDo Unable to stream pdf: headers already sent
//        /** @var ToDo $todo */
//        $todo = $this->getEntityManager()->getRepository(ToDo::class)->findOneBy(['archived' => 'true']);
//
//        ob_start();
//        /** @var  $crawler */
//        $crawler = $this->client->request('GET', '/quality_circle/todo/export/' . $todo->getId());
//        $getContent = ob_get_contents();
//        ob_end_clean();
//        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
//    }

    public function testActionPlan()
    {
        /** @var ToDoNew $todo */
        $todo = $this->getEntityManager()->getRepository(ToDoNew::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/action-plan/' . $todo->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Aktionsplan anlegen', $crawler->filter('h1')->text());

        $postData = [];
        $postData['cancel'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/quality_circle/todo/action-plan/' . $todo->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        unset($postData);
        $postData = [];
        $postData['action_plan'] = [];
        $postData['action_plan']['what'] = "Dies und das";
        $postData['action_plan']['how'] = "Malern";
        $postData['action_plan']['who'] = "Max Mustermann";
        $postData['action_plan']['when'] = ["day" => "1", "month" => "1", "year" => date("Y")];
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/quality_circle/todo/action-plan/' . $todo->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testActionPlanComplete()
    {
        /** @var ActionPlanNew $actionPlanNew */
        $actionPlan = $this->getEntityManager()->getRepository(ActionPlanNew::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/complete-action-plan/' . $actionPlan->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Aktionsplan beenden', $crawler->filter('h1')->text());

        $postData = [];
        $postData['action_plan_complete'] = [];
        $postData['action_plan_complete']['completed'] = 1;
        $postData['action_plan_complete']['note'] = "Super geklappt!";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/quality_circle/todo/complete-action-plan/' . $actionPlan->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testComplete()
    {
        /** @var ToDoNew $todo */
        $todo = $this->getEntityManager()->getRepository(ToDoNew::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/complete/' . $todo->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('To Do abschließen', $crawler->filter('h1')->text());

        $postData = [];
        $postData['to_do'] = [];
        $postData['to_do']['note'] = "Super geklappt!";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/quality_circle/todo/complete/' . $todo->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteActionPlan()
    {
        /** @var ActionPlanNew $actionPlanNew */
        $actionPlan = $this->getEntityManager()->getRepository(ActionPlanNew::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/delete-action-plan/' . $actionPlan->getId());
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteToDo()
    {
        /** @var ToDoNew $todo */
        $todo = $this->getEntityManager()->getRepository(ToDoNew::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/delete-todo/' . $todo->getId());
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testArchive()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/archive');
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShow()
    {
        /** @var ToDoNew $todo */
        $todo = $this->getEntityManager()->getRepository(ToDoNew::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/show/' . $todo->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** @var  School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => 'Testschule Neu']);
        $this->assertSame($school->getName() . ': To Do vom ' . $todo->getCreatedAt()->format('d.m.Y'), $crawler->filter('h1')->text());

    }
}
