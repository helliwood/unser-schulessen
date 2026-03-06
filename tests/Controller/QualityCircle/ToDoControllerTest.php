<?php

namespace App\Tests\Controller\QualityCircle;

use App\DataFixtures\UnitTestFixtures;
use App\Entity\QualityCheck\Answer;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use App\Entity\QualityCheck\Result;
use App\Entity\QualityCircle\ActionPlanNew;
use App\Entity\QualityCircle\ToDo;
use App\Entity\QualityCircle\ToDoNew;
use App\Entity\User;
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
        $this->ensureFinalisedQualityCheckForCurrentSchool();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neues To Do', $crawler->filter('h1')->text());

        $answer = $this->ensureAnswerForCurrentSchool();

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
        $todo = $this->ensureTodoForCurrentSchool();

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
        $todo = $this->ensureTodoForCurrentSchool();

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
        $actionPlan = $this->ensureActionPlanForCurrentSchool();

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
        $todo = $this->ensureTodoForCurrentSchool();

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
        $actionPlan = $this->ensureActionPlanForCurrentSchool();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/delete-action-plan/' . $actionPlan->getId());
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteToDo()
    {
        $todo = $this->ensureTodoForCurrentSchool();

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
        $todo = $this->ensureTodoForCurrentSchool();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/quality_circle/todo/show/' . $todo->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('To Do vom ' . $todo->getCreatedAt()->format('d.m.Y'), $crawler->filter('h1')->text());

    }

    private function ensureFinalisedQualityCheckForCurrentSchool(): void
    {
        if ($this->getLatestFinalisedResultForCurrentSchool() instanceof Result) {
            return;
        }

        $em = $this->getEntityManager();
        $user = $this->getFixtureUser();
        $school = $user->getCurrentSchool();

        /** @var Questionnaire|null $questionnaire */
        $questionnaire = $em->getRepository(Questionnaire::class)->findOneBy([], ['id' => 'ASC']);
        $this->assertNotNull($questionnaire);

        $result = new Result();
        $result->setSchool($school);
        $result->setCreatedBy($user);
        $result->setLastEditedBy($user);
        $result->setQuestionnaire($questionnaire);
        $result->setName('Test Ergebnis ' . uniqid('', true));
        $result->setFinalised(true);
        $result->setFinalisedBy($user);
        $result->setFinalisedAt(new \DateTime());
        $em->persist($result);
        $em->flush();

        /** @var Question|null $question */
        $question = $em->getRepository(Question::class)->findOneBy([], ['id' => 'ASC']);
        $this->assertNotNull($question);

        $answer = new Answer();
        $answer->setResult($result);
        $answer->setQuestion($question);
        $answer->setAnswer(Answer::ANSWER_TRUE);
        $em->persist($answer);
        $em->flush();
    }

    private function ensureAnswerForCurrentSchool(): Answer
    {
        $this->ensureFinalisedQualityCheckForCurrentSchool();
        $result = $this->getLatestFinalisedResultForCurrentSchool();
        $this->assertInstanceOf(Result::class, $result);

        /** @var Answer|null $answer */
        $answer = $this->getEntityManager()->getRepository(Answer::class)->findOneBy(['result' => $result], ['id' => 'ASC']);
        $this->assertNotNull($answer);

        return $answer;
    }

    private function ensureTodoForCurrentSchool(): ToDoNew
    {
        $school = $this->getFixtureUser()->getCurrentSchool();
        /** @var ToDoNew|null $todo */
        $todo = $this->getEntityManager()->getRepository(ToDoNew::class)->findOneBy(['school' => $school], ['id' => 'DESC']);
        if ($todo instanceof ToDoNew) {
            return $todo;
        }

        $todo = new ToDoNew();
        $todo->setCreatedBy($this->getFixtureUser());
        $todo->setSchool($school);
        $todo->setName('ToDo vom ' . $todo->getCreatedAt()->format('d.m.Y'));
        $todo->setAnswer($this->ensureAnswerForCurrentSchool());
        $this->getEntityManager()->persist($todo);
        $this->getEntityManager()->flush();

        return $todo;
    }

    private function ensureActionPlanForCurrentSchool(): ActionPlanNew
    {
        $todo = $this->ensureTodoForCurrentSchool();

        /** @var ActionPlanNew|null $actionPlan */
        $actionPlan = $this->getEntityManager()->getRepository(ActionPlanNew::class)->findOneBy(['toDo' => $todo], ['id' => 'DESC']);
        if ($actionPlan instanceof ActionPlanNew) {
            return $actionPlan;
        }

        $actionPlan = new ActionPlanNew();
        $actionPlan->setToDo($todo);
        $actionPlan->setCreatedBy($this->getFixtureUser());
        $actionPlan->setWhat('Test Maßnahme');
        $actionPlan->setHow('Test Umsetzung');
        $actionPlan->setWho('Test Person');
        $actionPlan->setWhen(new \DateTime());
        $this->getEntityManager()->persist($actionPlan);
        $this->getEntityManager()->flush();

        return $actionPlan;
    }

    private function getLatestFinalisedResultForCurrentSchool(): ?Result
    {
        return $this->getEntityManager()->getRepository(Result::class)->findOneBy(
            ['school' => $this->getFixtureUser()->getCurrentSchool(), 'finalised' => true],
            ['id' => 'DESC']
        );
    }

    private function getFixtureUser(): User
    {
        /** @var User|null $user */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => UnitTestFixtures::TESTUSER_EMAIL]);
        $this->assertNotNull($user);

        return $user;
    }
}
