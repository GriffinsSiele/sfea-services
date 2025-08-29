<?php

declare(strict_types=1);

namespace App\Controller\Identity;

use App\Repository\SystemUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/identity/login/{userIdentity}', methods: Request::METHOD_GET)]
class LoginController extends AbstractController
{
    public function __construct(
        private readonly SystemUserRepository $systemUserRepository,
    ) {
    }

    /*
     * Для сервиса авторизации дает информацию о том, зарегистрирован аккаунт или нет
     * На всякий случай от перебора логинов сделана примитивнейшая защита
     * Если аккаунт найден, то секунда четная в ответе, если нет - нечетная
     * А поля id и status не имеют практического смысла - пусть с ними развлекаются, кто хочет
     */
    public function __invoke(string $userIdentity): JsonResponse
    {
        $qb = $this->systemUserRepository->createQueryBuilder('u');
        $qb
            ->select('1')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('u.login', ':userIdentity'),
                    $qb->expr()->eq('u.email', ':userIdentity'),
                ),
            )
            ->setParameter('userIdentity', $userIdentity);

        $result = \uniqid(\md5((string) \microtime(true)), true);
        $createdAt = \time();

        if (0 === \random_int(0, 1)) {
            $status = \md5($result);
        } else {
            $status = \sha1($result);
        }

        if (null !== $qb->getQuery()->getOneOrNullResult()) {
            if (1 === $createdAt % 2) {
                ++$createdAt;
            }
        } elseif (0 === $createdAt % 2) {
            --$createdAt;
        }

        return $this->json([
            'id' => \password_hash(\uniqid(\md5((string) \microtime(true)), true), \PASSWORD_BCRYPT),
            'status' => $status,
            'created_at' => new \DateTimeImmutable('@'.$createdAt),
        ]);
    }
}
