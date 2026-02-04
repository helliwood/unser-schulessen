<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-26
 * Time: 14:27
 */

namespace App\Security;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{

    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(AuthorizationChecker $authorizationChecker, EntityManager $entityManager)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityManager = $entityManager;
    }

    /**
     *
     * @param InteractiveLoginEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            /** @var \App\Entity\User $user */
            $user = $event->getAuthenticationToken()->getUser();
            $user->setLastLogin($user->getCurrentLogin());
            $user->setCurrentLogin(new \DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush($user);
        } elseif ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            /** @var \App\Entity\User $user */
            $user = $event->getAuthenticationToken()->getUser();
            $user->setLastLogin($user->getCurrentLogin());
            $user->setCurrentLogin(new \DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush($user);
        }
    }
}
