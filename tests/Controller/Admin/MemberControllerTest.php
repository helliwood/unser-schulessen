<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\UnitTestFixtures;
use App\Entity\PersonType;
use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class MemberControllerTest extends AbstractTestController
{
    protected $client = null;

    const MEMBERCONTROLLER_EMAIL = "test3@helliwood.com";
    const MEMBERCONTROLLER_PERSONTYPE = PersonType::TYPE_HEADMASTER;
    const MEMBERCONTROLLER_ROLE = "ROLE_FOOD_COMMISSIONER";
    const MEMBERCONTROLLER_SENDINVITATION = "1";

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testListAjax()
    {
        /** @var School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => 'Testschule']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/school/members/' . $school->getId() . '/', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNew()
    {
        /** @var School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => UnitTestFixtures::TESTUSER_SCHOOL]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/school/members/' . $school->getId() . '/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Neues Mitglied', $crawler->filter('h1')->text());

        $postData = ['user_has_school' => []];
        $postData['user_has_school']['email'] = self::MEMBERCONTROLLER_EMAIL;
        $postData['user_has_school']['personType'] = self::MEMBERCONTROLLER_PERSONTYPE;
        $postData['user_has_school']['sendInvitation'] = self::MEMBERCONTROLLER_SENDINVITATION;
        $postData['user_has_school']['role'] = self::MEMBERCONTROLLER_ROLE;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData['cancel'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        /** @var School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => 'Testschule']);

        /** @var UserHasSchool $userHasSchool */
        $userHasSchool = $this->getEntityManager()->getRepository(UserHasSchool::class)->findOneBy(['school' => $school], ['createdAt' => 'DESC']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/edit/' . $userHasSchool->getUser()->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($userHasSchool->getUser()->getDisplayName(), $crawler->filter('h1')->text());

        $postData = ['user_has_school' => []];
        $postData['user_has_school']['personType'] = "Verpflegungsbeauftragte(r)";
        $postData['user_has_school']['role'] = "ROLE_FOOD_COMMISSIONER";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/edit/' . $userHasSchool->getUser()->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData['cancel'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/edit/' . $userHasSchool->getUser()->getId(), $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete()
    {
        /** @var School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => 'Testschule']);

        /** @var UserHasSchool $userHasSchool */
        $userHasSchool = $this->getEntityManager()->getRepository(UserHasSchool::class)->findOneBy(['school' => $school], ['createdAt' => 'DESC']);

        $postData = ['action' => 'delete_invitation', 'user_id' => $userHasSchool->getUser()->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/', $postData);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }


    public function testConsultant2School()
    {
        /** @var User $user ^ */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => UnitTestFixtures::TESTUSER_EMAIL]);

        /** @var School $school ^ */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => UnitTestFixtures::TESTUSER_SCHOOL]);

        $postData['consultant_to_school']['user'] = $user->getId();
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/new-consultant', $postData);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

    }

    public function testSwitchUser()
    {
        $email = UnitTestFixtures::TESTUSER2_EMAIL;
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $email]);
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/?_switch_user=' . $email);

        $this->client->followRedirects();
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/school/show/'. $user->getId() .'?_switch_user=_exit');

    }

}
