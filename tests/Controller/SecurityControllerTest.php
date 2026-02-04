<?php


namespace App\Tests\Controller;


use App\DataFixtures\UnitTestFixtures;
use App\Entity\PersonType;
use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends AbstractTestController
{
    protected $client = null;

    protected $em;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testIndexLogin()
    {
        $this->client->followRedirects();
        $this->logOut();

        /** falsche Login Daten */
        /** @var  $crawler */
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('submit')->form();

        $form['_username']->setValue('FALSCHE@EMAIL.ADRESSE');
        $form['_password']->setValue('FALSCHES PASSWORT');
        $this->client->submit($form);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** richtige login Daten */
        /** @var  $crawler */
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('submit')->form();
        $form['_username']->setValue(UnitTestFixtures::TESTUSER_EMAIL);
        $form['_password']->setValue(UnitTestFixtures::TESTUSER_PASSWORD);
        $this->client->submit($form);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

    }

    public function testReset()
    {
        $this->logOut();
        $this->em = $this->getEntityManager();
        $this->client->followRedirects();

        /** Email anfordern, wenn man auf [Passwort vergessen] klickt */
        /** @var  $crawler */
        $crawler = $this->client->request('GET', '/reset');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('save')->form();
        $form['reset_password']['email']->setValue(UnitTestFixtures::TESTUSER_EMAIL);
        $this->client->submit($form);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** Link aus der Email folgen die man dann bekommt */
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => UnitTestFixtures::TESTUSER_EMAIL]);

        $token = md5($user->getEmail() . $user->getCreatedAt()->format("Y-m-d H:i:s"));
        $crawler = $this->client->request('GET', '/login/' . $token);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('save')->form();
        $form['create_new_password']['password']['first']->setValue(UnitTestFixtures::TESTUSER_PASSWORD);
        $form['create_new_password']['password']['second']->setValue(UnitTestFixtures::TESTUSER_PASSWORD);
        $this->client->submit($form);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** Wenn man einen Falschen Token abtippt/den Link manipuliert oä */
        /** @var  $crawler */
        $crawler = $this->client->request('GET', '/login/FalscherToken');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testProfile()
    {
        $this->client->followRedirects();

        $crawler = $this->client->request('GET', '/profile');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('save')->form();
        $form['profile']['salutation']->setValue(UnitTestFixtures::TESTUSER_SALUTATION);
        $form['profile']['academicTitle']->setValue(UnitTestFixtures::TESTUSER_ACADEMIC_TITLE);
        $form['profile']['firstName']->setValue(UnitTestFixtures::TESTUSER_FIRST_NAME);
        $form['profile']['lastName']->setValue(UnitTestFixtures::TESTUSER_LAST_NAME);
        $this->client->submit($form);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testChangePassword()
    {
        $this->client->followRedirects();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/change-password');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Passwort ändern', $crawler->filter('h1')->text());

        /** Passwort falsch eingegeben */
        /** @var  $form */
        $form = $crawler->selectButton('save')->form();
        $form['password_form']['oldPassword']->setValue('Falsches Passwort');
        $form['password_form']['newPassword']['first']->setValue(UnitTestFixtures::TESTUSER_PASSWORD);
        $form['password_form']['newPassword']['second']->setValue(UnitTestFixtures::TESTUSER_PASSWORD);
        $this->client->submit($form);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** neues Passwort falsch wiederholt */
        $form['password_form']['oldPassword']->setValue(UnitTestFixtures::TESTUSER_PASSWORD);
        $form['password_form']['newPassword']['first']->setValue('Falsch');
        $form['password_form']['newPassword']['second']->setValue('Wiederholt');
        $this->client->submit($form);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** Passwort erfolgreich geändert */
        $form['password_form']['oldPassword']->setValue(UnitTestFixtures::TESTUSER_PASSWORD);
        $form['password_form']['newPassword']['first']->setValue(UnitTestFixtures::TESTUSER_PASSWORD);
        $form['password_form']['newPassword']['second']->setValue(UnitTestFixtures::TESTUSER_PASSWORD);
        $this->client->submit($form);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    const SECURITYCONTROLLER_EMAIL = "invitationTest@helliwood.com";

    public function testInvitation()
    {
        $this->client->followRedirects();

        /** @var School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => UnitTestFixtures::TESTUSER_SCHOOL]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/school/members/' . $school->getId() . '/new');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
//        $this->assertStringContainsString('Neues Mitglied', $this->client->getResponse()->getContent());

        $form = $crawler->selectButton('save')->form();
        $form['user_has_school'] = [];
        $form['user_has_school']['email']->setValue(self::SECURITYCONTROLLER_EMAIL);
        $form['user_has_school']['personType']->setValue(PersonType::TYPE_HEADMASTER);
        $form['user_has_school']['sendInvitation']->setValue("1");
        $form['user_has_school']['role']->setValue(User::ROLE_FOOD_COMMISSIONER);
        $this->client->submit($form);

        /** @var $user */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => self::SECURITYCONTROLLER_EMAIL]);
        $token = md5($user->getEmail() . $user->getCreatedAt()->format("Y-m-d H:i:s"));

        /** @var School $school */
        $school = $this->getEntityManager()->getRepository(School::class)->findOneBy(['name' => UnitTestFixtures::TESTUSER_SCHOOL]);

        /** @var $crawler */
        $crawler = $this->client->request('GET', '/invitation/' . $token . '/' . $user->getId() . '/' . $school->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('save')->form();
        $form['activate']['person']['salutation']->setValue('Herr');
        $form['activate']['person']['lastName']->setValue('Inviter');
        $form['activate']['password']['first']->setValue('asd');
        $form['activate']['password']['second']->setValue('asd');
        $this->client->submit($form);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

//      Einladung doppelt annehmen
        $this->client->request('GET', '/invitation/' . $token . '/' . $user->getId() . '/' . $school->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

//      Falschen Token angegeben
        $this->client->request('GET', '/invitation/FALSCHERTOKEN/' . $user->getId() . '/' . $school->getId());

//      Einladung zurückgezogen
        /** @var UserHasSchool $userHasSchool */
        $userHasSchool = $this->getEntityManager()->getRepository(UserHasSchool::class)->findOneBy([], ['createdAt' => 'DESC']);

        $postData = ['action' => 'delete_invitation', 'user_id' => $user->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/members/', $postData);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);

        $crawler = $this->client->request('GET', '/invitation/' . $token . '/' . $user->getId() . '/' . $school->getId());

//        Fehlende Tests
//        Blockieren - da ein JS Popup erscheint "Wollen Sie XY wirklich sperren?"
//        $crawler = $this->client->request('GET', '/master_data');
//        $crawler->filter('button:contains("Sperren")');

//        Einladung ablehnen
    }
}