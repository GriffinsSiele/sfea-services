<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SystemUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

class SystemUserRepository extends ServiceEntityRepository
{
    public function __construct(
        private readonly RequestStack $requestStack,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, SystemUser::class);
    }

    public function findOneByLogin(string $login): ?SystemUser
    {
        $qb = $this->createQueryBuilder('su');
        $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('su.login', ':login'),
                    $qb->expr()->orX(
                        $qb->expr()->isNull('su.locked'),
                        $qb->expr()->eq('su.locked', 0),
                        $qb->expr()->gte('su.locked', 2),
                    ),
                    $qb->expr()->orX(
                        $qb->expr()->isNull('su.allowedIp'),
                        $qb->expr()->gt('locate(:remoteAddr, su.allowedIp)', 0),
                    )
                ),
            )
            ->setParameters([
                'login' => $login,
                'remoteAddr' => $this->getClientIp(),
            ])
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function getClientIp(): ?string
    {
        return $this->requestStack->getCurrentRequest()?->getClientIp();
    }
}
