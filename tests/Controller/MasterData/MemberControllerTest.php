<?php


namespace App\Tests\Controller\MasterData;


use App\DataFixtures\UnitTestFixtures;
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

        $email = 'member-new-'.uniqid('', true).'@example.invalid';
        $postData = ['user_has_school' => []];
        $postData['user_has_school']['email'] = $email;
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
        $userHasSchool = $this->getOrCreateBlockableUserHasSchool();
        $user = $userHasSchool->getUser();

        $this->assertNotNull($user);
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

    private function getOrCreateBlockableUserHasSchool(): UserHasSchool
    {
        $em = $this->getEntityManager();
        /** @var User|null $currentUser */
        $currentUser = $em->getRepository(User::class)->findOneBy(['email' => UnitTestFixtures::TESTUSER_EMAIL]);
        $this->assertNotNull($currentUser);
        $currentSchool = $currentUser->getCurrentSchool();

        /** @var UserHasSchool|null $userHasSchool */
        $userHasSchool = $em->createQueryBuilder()
            ->select('uhs')
            ->from(UserHasSchool::class, 'uhs')
            ->join('uhs.user', 'u')
            ->where('uhs.school = :school')
            ->andWhere('u != :currentUser')
            ->setParameter('school', $currentSchool)
            ->setParameter('currentUser', $currentUser)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($userHasSchool !== null) {
            if ($userHasSchool->getState() !== UserHasSchool::STATE_ACCEPTED
                && $userHasSchool->getState() !== UserHasSchool::STATE_BLOCKED) {
                $userHasSchool->setState(UserHasSchool::STATE_ACCEPTED);
                $em->persist($userHasSchool);
                $em->flush();
            }
            return $userHasSchool;
        }

        $personType = $em->getRepository(PersonType::class)->find(PersonType::TYPE_FOOD_COMMISSIONER);
        $this->assertNotNull($personType);

        $user = new User();
        $user->setEmail('member-block-'.uniqid('', true).'@example.invalid');
        $user->setState(User::STATE_ACTIVE);
        $user->setCurrentSchool($currentSchool);
        $em->persist($user);

        $userHasSchool = new UserHasSchool();
        $userHasSchool->setUser($user);
        $userHasSchool->setSchool($currentSchool);
        $userHasSchool->setPersonType($personType);
        $userHasSchool->setRole(User::ROLE_FOOD_COMMISSIONER);
        $userHasSchool->setState(UserHasSchool::STATE_ACCEPTED);
        $em->persist($userHasSchool);
        $em->flush();

        return $userHasSchool;
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
