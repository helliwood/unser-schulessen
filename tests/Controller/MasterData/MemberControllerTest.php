<?php


namespace App\Tests\Controller\MasterData;


use App\Entity\PersonType;
use App\Entity\User;
use App\Entity\UserHasSchool;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class MemberControllerTest extends AbstractTestController
{
    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testList()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/members/', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNew()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/members/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neues Mitglied', $crawler->filter('h1')->text());

        $postData = ['user_has_school' => []];
        $postData['user_has_school']['email'] = "karg@helliwood.de";
        $postData['user_has_school']['personType'] = PersonType::TYPE_HEADMASTER;
        $postData['user_has_school']['sendInvitation'] = "1";
        $postData['user_has_school']['role'] = User::ROLE_FOOD_COMMISSIONER;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/members/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData['cancel'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/members/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        /** @var UserHasSchool $userHasSchool */
        $userHasSchool = $this->getEntityManager()->getRepository(UserHasSchool::class)->findOneBy([], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/members/edit/' . $userHasSchool->getUser()->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($userHasSchool->getUser()->getDisplayName(), $crawler->filter('h1')->text());

        $postData = ['user_has_school' => []];
        $postData['user_has_school']['personType'] = "Verpflegungsbeauftragte(r)";
        $postData['user_has_school']['role'] = "ROLE_FOOD_COMMISSIONER";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/members/edit/' . $userHasSchool->getUser()->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData['cancel'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/members/edit/' . $userHasSchool->getUser()->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testBlockUnblockUser()
    {
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'TestAccept@helliwood.de']);
        $postData = [];
        $postData["action"] = "block_user";
        $postData["user_id"] = $user->getId();
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/members/', $postData);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/members/', $postData);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        /** @var UserHasSchool $userHasSchool */
        $userHasSchool = $this->getEntityManager()->getRepository(UserHasSchool::class)->findOneBy([], ['createdAt' => 'DESC']);

        $postData = ['action' => 'delete_invitation', 'user_id' => $userHasSchool->getUser()->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/members/', $postData);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }
}
