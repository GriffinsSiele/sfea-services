<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Bulk;
use App\Entity\SystemUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BulkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bulk::class);
    }

    public function findOneByUserAndId(SystemUser $user, int $id): ?Bulk
    {
        $qb = $this->createQueryBuilder('b');
        $qb
            ->andWhere($qb->expr()->eq('b.user', ':user'))
            ->andWhere($qb->expr()->eq('b.id', ':id'))
            ->setParameter('id', $id)
            ->setParameter('user', $user);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findStatusById(int $id): ?int
    {
        $qb = $this->createQueryBuilder('b');
        $qb
            ->select('b.status')
            ->where($qb->expr()->eq('b.id', ':id'))
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult()['status'] ?? null;
    }

    public function findStatusAndCountsById(int $id): ?array
    {
        $qb = $this->createQueryBuilder('b');
        $qb
            ->select([
                'b.processedRows',
                'b.status',
                'b.successRows',
                'b.totalRows',
            ])
            ->where($qb->expr()->eq('b.id', ':id'))
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function incrementCountersById(int $id, bool $isSuccess): bool
    {
        $this->getEntityManager()->beginTransaction();

        $qb = $this->createQueryBuilder('b');
        $qb
            ->select([
                'b.totalRows',
                'b.processedRows',
                'b.successRows',
            ])
            ->where($qb->expr()->eq('b.id', ':id'))
            ->setParameter('id', $id);

        $res = $qb->getQuery()->getOneOrNullResult();
        $totalRows = $res['totalRows'];
        $processedRows = $res['processedRows'] ?? 0;
        $successRows = $res['successRows'] ?? 0;

        ++$processedRows;

        if ($isSuccess) {
            ++$successRows;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->update(Bulk::class, 'b')
            ->set('b.processedRows', ':processedRows')
            ->set('b.successRows', ':successRows')
            ->where($qb->expr()->eq('b.id', ':id'))
            ->setParameter('id', $id)
            ->setParameter('processedRows', $processedRows)
            ->setParameter('successRows', $successRows);

        $qb->getQuery()->execute();

        $this->getEntityManager()->commit();

        return $totalRows === $processedRows;
    }

    public function updateStatusById(int $id, int $status): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->update(Bulk::class, 'b')
            ->set('b.status', ':status')
            ->where($qb->expr()->eq('b.id', ':id'))
            ->setParameter('id', $id)
            ->setParameter('status', $status);

        if (Bulk::STATUS_COMPLETED === $status
            || Bulk::STATUS_NOT_COMPLETED === $status
        ) {
            $qb->set('b.processedAt', ':processedAt');
            $qb->setParameter('processedAt', new \DateTimeImmutable());
        }

        $qb->getQuery()->execute();
    }
}
