<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    /**
     * This method is called before the user is authenticated.
     * It can be used to check if the user is allowed to authenticate.
     *
     * @param UserInterface $user
     * @throws CustomUserMessageAccountStatusException
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof \App\Entity\User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Votre compte est inactif. Veuillez contacter un administrateur.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // This method can be used to perform checks after authentication
    }
}