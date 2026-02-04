<?php

namespace App\Tests\Controller;

use App\DataFixtures\UnitTestFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class AbstractTestController extends WebTestCase
{
    public $school;

    /**
     * return void
     * @param string $email
     * @param string $password
     */
    protected function logIn($email = UnitTestFixtures::TESTUSER_EMAIL, $password = UnitTestFixtures::TESTUSER_PASSWORD)
    {
        $session = $this->client->getContainer()->get('session');
        $authenticationManager = static::$container->get('security.authentication.manager');
        $firewall = 'main';
        $token = $authenticationManager->authenticate(
            new UsernamePasswordToken(
                $email,
                $password,
                $firewall
            )
        );
        $session->set('_security_' . $firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    /**
     * @return void
     */
    protected function logOut(){
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/logout');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return self::$container->get('doctrine.orm.entity_manager');
    }

    /**
     * @return UserInterface|User
     */
    protected function getUser(): UserInterface
    {
        return self::$container->get('security.token_storage')->getToken()->getUser();
    }
}
