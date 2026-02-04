<?php


namespace App\Tests\Controller;


use App\Entity\PersonType;
use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class IndexControllerTest extends AbstractTestController
{
    protected $client = null;
    protected $em;

    const accept = "TestAccept@helliwood.de";
    const decline = "TestDecline@helliwood.de";

    public function setUp(): void
    {
        $this->client = static::createClient();

        $this->em = $this->getEntityManager();

        $this->logIn();
    }

    public function testIndex()
    {
        $this->logIn();
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Überblick', $crawler->filter('h1')->text());
        //$this->assertSame(2, $crawler->filterXPath("//button[contains(@disabled, 'disabled')]")->count());
    }


    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testAcceptInvite()
    {
        /** @var  School $school */
        $school = $this->em->getRepository(School::class)->findOneBy(['name' => 'Testschule']);

        $postData = ['user_has_school' => []];
        $postData['user_has_school']['email'] = self::accept;
        $postData['user_has_school']['personType'] = PersonType::TYPE_HEADMASTER;
        $postData['user_has_school']['sendInvitation'] = "1";
        $postData['user_has_school']['role'] = User::ROLE_HEADMASTER;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());


        $user = $this->em->getRepository(User::class)->findOneBy(['email' => self::accept]);
        $user->setState(User::STATE_ACTIVE);

        $user->setPassword('$2y$13$2P0qdvoVTmbzJSDNybMayeehoANXNHec.LoMrcUmcpLyrELAGI0ke');

        $this->em->persist($user);
        $this->em->flush();

        $this->logOut();

        $this->logIn(self::accept, 'lol');

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/accept_invite/' . $school->getId());
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testDeclineInvite()
    {
        /** @var  School $school */
        $school = $this->em->getRepository(School::class)->findOneBy(['name' => 'Testschule']);

        $postData = ['user_has_school' => []];
        $postData['user_has_school']['email'] = self::decline;
        $postData['user_has_school']['personType'] = "Schulträger";
        $postData['user_has_school']['sendInvitation'] = "1";
        $postData['user_has_school']['role'] = User::ROLE_FOOD_COMMISSIONER;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => self::decline]);
        $user->setState(User::STATE_ACTIVE);

        $user->setPassword('$2y$13$2P0qdvoVTmbzJSDNybMayeehoANXNHec.LoMrcUmcpLyrELAGI0ke');

        $this->em->persist($user);
        $this->em->flush();

        $this->logOut();

        $this->logIn(self::decline, 'lol');

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/decline_invite/' . $school->getId());
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testChangeSchool()
    {
//        $this->client->catchExceptions(false);
//        /** @var Crawler $crawler */
//        $this->client->request('POST', '/admin/school/members/999/new');
////        dump($this->client->getResponse()->getContent());die;
//        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $postData = ['school' => ['address' => []]];
        $postData['school']['name'] = "Change Schule";
        $postData['school']['schoolNumber'] = "123";
        $postData['school']['headmaster'] = "";
        $postData['school']['phoneNumber'] = "";
        $postData['school']['faxNumber'] = "";
        $postData['school']['emailAddress'] = "";
        $postData['school']['webpage'] = "";
        $postData['school']['educationAuthority'] = "";
        $postData['school']['schoolType'] = "";
        $postData['school']['schoolOperator'] = "";
        $postData['school']['particularity'] = "";
        if ($_ENV['APP_STATE_COUNTRY'] == 'rp') {
            $postData['school']['auditEnd'] = "2025-03-22";
        }
        $postData['school']['address']['street'] = "";
        $postData['school']['address']['postalcode'] = "";
        $postData['school']['address']['city'] = "Berlin";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        /** @var  School $school */
        $school = $this->em->getRepository(School::class)->findOneBy(['name' => 'Change Schule']);

        $postData = ['user_has_school' => []];
        $postData['user_has_school']['email'] = self::accept;
        $postData['user_has_school']['personType'] = PersonType::TYPE_HEADMASTER;
        $postData['user_has_school']['sendInvitation'] = "1";
        $postData['user_has_school']['role'] = User::ROLE_FOOD_COMMISSIONER;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/school/members/' . $school->getId() . '/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $this->logOut();

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => self::accept]);
        $userHasSchool = $this->em->getRepository(UserHasSchool::class)->findOneBy(['user' => $user, 'school' => $school]);
        $userHasSchool->setState(User::STATE_ACTIVE);

        $this->em->persist($userHasSchool);
        $this->em->flush();

        $this->logIn(self::accept, 'lol');

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/change_school/' . $school->getId());

        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

    }
}
