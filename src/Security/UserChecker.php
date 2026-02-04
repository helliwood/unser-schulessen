<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-04-10
 * Time: 13:26
 */

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{

    /**
     * Checks the user account before authentication.
     *
     * @param User|UserInterface $user
     * @throws AccountStatusException
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        if ($user->getState() === User::STATE_BLOCKED) {
            throw new \Symfony\Component\Security\Core\Exception\LockedException('Benutzer gesperrt!');
        }

        if ($user->getState() === User::STATE_NOT_ACTIVATED) {
            throw new \Symfony\Component\Security\Core\Exception\DisabledException('Benutzer noch nicht aktiviert!');
        }

        if ($user->getState() !== User::STATE_ACTIVE) {
            throw new \Symfony\Component\Security\Core\Exception\LockedException('Benutzer nicht aktiv!');
        }
    }

    /**
     * Checks the user account after authentication.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @param User|UserInterface $user
     * @throws AccountStatusException
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if (! $user instanceof User) {
            return;
        }
    }
}
