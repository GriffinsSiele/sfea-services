<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\SystemUser;
use App\Repository\SystemUserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SystemUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly SystemUserRepository $systemUserRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->systemUserRepository->findOneByLogin($identifier);

        if (null === $user) {
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($identifier);

            throw $exception;
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): ?UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SystemUser::class === $class
            || \is_subclass_of($class, SystemUser::class);
    }
}
