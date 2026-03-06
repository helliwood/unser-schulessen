<?php

namespace App\Tests\Controller;

use App\DataFixtures\UnitTestFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
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
        unset($password);
        $user = static::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => $email]);
        if (! $user instanceof User) {
            throw new \RuntimeException(sprintf('Test user with email "%s" not found.', $email));
        }
        $this->client->loginUser($user, 'main');
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
        return static::getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return UserInterface|User
     */
    protected function getUser(): UserInterface
    {
        return static::getContainer()->get('security.token_storage')->getToken()->getUser();
    }
}
