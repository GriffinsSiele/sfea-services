<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Okato;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OkatoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Okato::class);
    }

    public function existsByTer(string $ter): bool
    {
        $ter = \str_pad($ter, 2, '0', \STR_PAD_LEFT);

        if (2 !== \strlen($ter)) {
            return false;
        }

        $qb = $this->createQueryBuilder('o');
        $qb
            ->select('o')
            ->where($qb->expr()->eq('o.ter', ':ter'))
            ->setParameter('ter', $ter)
            ->setMaxResults(1);

        return null !== $qb->getQuery()->getOneOrNullResult();
    }
}
