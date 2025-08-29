<?php

declare(strict_types=1);

namespace App\Controller\Identity;

use App\Entity\SystemUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/identity', methods: Request::METHOD_GET)]
class WhoAmIController extends AbstractController
{
    public function __invoke(#[CurrentUser] SystemUser $systemUser): JsonResponse
    {
        return $this->json([
            'id' => $systemUser->getId(),
            'login' => $systemUser->getLogin(),
            'accessLevel' => $systemUser->getAccessLevel(),
            'accessArea' => $systemUser->getAccessArea(),
            'email' => $systemUser->getEmail(),
        ]);
    }
}
