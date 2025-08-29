<?php

declare(strict_types=1);

namespace App\Security\Hasher;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class SystemUserHasher implements PasswordHasherInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return \md5($plainPassword);
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        if ('' === $plainPassword) {
            return false;
        }

        if (\md5($plainPassword) === $hashedPassword) {
            return true;
        }

        if ($plainPassword === $hashedPassword) {
            //            $this->logger->warning('Non hashed password user detected');

            return true;
        }

        return false;
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}
