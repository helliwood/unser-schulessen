<?php
/**
 * Created by PhpStorm.
 * User: melchior
 * Date: 2020-03-19
 * Time: 13:06
 */

namespace App\Controller\Admin;

use App\DataFixtures\UnitTestFixtures;
use App\Entity\Person;
use App\Entity\User;
use App\Entity\School;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class EmployeeControllerTest extends AbstractTestController
{

    protected $client = null;

    public function setUp() {
        $this->client = static::createClient();

        $this->client->followRedirects();

        $this->logIn();
    }

    public function testIndex() {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/employee/');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertSame('Mitarbeiter', $crawler->filter('h3')->text());
    }

    public function testIndexAjax($findInAjax = 'Test') {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/employee/?page=1&size=10&sort=displayName&sortDesc=false', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        if ($this->is_in_array($JSON_response, $findInAjax)) {
            $this->assertTrue(true, true);
            return true;
        }
        return false;
    }

    public function testListSchools() {
        /** @var User $user ^ */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'test@helliwood.com']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/employee/' . $user->getId() . '/schools?page=1&size=10&sort=&sortDesc=false');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertSame('Schulen von ' . $user->getDisplayName(), $crawler->filter('h3')->text());

    }

    public function testListSchoolsAjax() {
        /** @var User $user ^ */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'test@helliwood.com']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/employee/' . $user->getId() . '/schools?page=1&size=10&sort=&sortDesc=false', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->is_in_array($JSON_response, 'Testschule'));

    }

    private function is_in_array($haystack, $needle): ?bool {
        if (is_array($haystack)) {
            foreach ($haystack as $item) {
                if ($this->is_in_array($item, $needle) === true)
                    return true;
            }
        } elseif (strpos($haystack, $needle) !== false) {
            return true;
        }
        return false;
    }

    public function setUpNew() {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/employee/new');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertSame('Neuer Mitarbeiter', $crawler->filter('h1')->text());

        return $crawler;
    }

    public function testTestNewCancel() {
        $crawler = $this->setUpNew();

        $postData['cancel'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/employee/new', $postData);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

    }

    public function testNew() {

        $crawler = $this->setUpNew();

        $postData = ['employee' => []];
        $postData['employee']['academic_title'] = 'Dr.';
        $postData['employee']['first_name'] = 'Karl';
        $postData['employee']['last_name'] = 'Koch';
        $postData['employee']['email'] = 'karl@koch.de';
        $postData['employee']['newPassword'] = '$argon2id$v=19$m=65536,t=4,p=1$MMLFE5rpKvQ/gTQAkyn94Q$8qeDjmfm6wqWDIWKknce2QBBUm2PArTYu5EYJVpzaXw';
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/employee/new', $postData);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());


        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/employee/?page=1&size=10', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->is_in_array($JSON_response, 'Koch'));
    }

    public function testSetupEdit() {

        /** @var User $user ^ */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'test@helliwood.com']);

        $url = '/admin/employee/' . $user->getId() . '/edit';

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', $url);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        return $crawler;
    }

    public function testTestEditCancel() {
        $crawler = $this->testSetupEdit();

        $postData['cancel'] = "";

        /** @var User $user ^ */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'test@helliwood.com']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/employee/' . $user->getId() . '/edit', $postData);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

    }

    public function testEditUser() {
        $this->client->followRedirects();

        $this->testSetupEdit();

        /** @var User $user */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'test@helliwood.com']);

        // Change last_name to Testeo
        $postData['employee']['salutation'] = 'Frau';
        $postData['employee']['academic_title'] = 'Dr.';
        $postData['employee']['first_name'] = 'Test';
        $postData['employee']['last_name'] = 'Testeo';
        $postData['employee']['email'] = UnitTestFixtures::TESTUSER_EMAIL;
        $postData['employee']['roles'][] = User::ROLE_ADMIN;
        $postData['employee']['roles'][] = User::ROLE_CONSULTANT;
        $postData['employee']['newPassword'] = UnitTestFixtures::TESTUSER_PASSWORD;
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/employee/' . $user->getId() . '/edit', $postData);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $res = $this->getEntityManager()
            ->getRepository(Person::class)
            ->findOneBy(['lastName' => 'Testeo']);

        $this->assertTrue($res->getLastName() == 'Testeo');


        //renaming back to Tester
        $rewindData['employee']['salutation'] = null;
        $rewindData['employee']['academic_title'] = null;
        $rewindData['employee']['first_name'] = UnitTestFixtures::TESTUSER_FIRST_NAME;
        $rewindData['employee']['last_name'] = UnitTestFixtures::TESTUSER_LAST_NAME;
        $rewindData['employee']['email'] = UnitTestFixtures::TESTUSER_EMAIL;
        $rewindData['employee']['roles'][] = User::ROLE_CONSULTANT;
        $rewindData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/employee/' . $user->getId() . '/edit', $rewindData);
    }
}
