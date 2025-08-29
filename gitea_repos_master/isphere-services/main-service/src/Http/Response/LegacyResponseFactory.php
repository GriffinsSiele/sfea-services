<?php

declare(strict_types=1);

namespace App\Http\Response;

use App\Entity\SystemUser;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LegacyResponseFactory implements ContainerAwareInterface, LoggerAwareInterface
{
    use ContainerAwareTrait;
    use LoggerAwareTrait;

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly EntityManagerInterface $entityManager,
        private readonly Kernel $kernel,
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function create(SystemUser $user, string $filename): LegacyResponse
    {
        return new LegacyResponse(
            $this->authorizationChecker,
            $this->container,
            $this->entityManager,
            $this->kernel,
            $this->logger,
            $this->requestStack->getCurrentRequest(),
            $this->tokenStorage,
            $this->urlGenerator,
            $user,
            $filename,
        );
    }
}
