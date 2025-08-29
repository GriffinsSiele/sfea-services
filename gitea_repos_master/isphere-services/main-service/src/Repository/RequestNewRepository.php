<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RequestNew;
use App\Entity\SystemUser;
use App\Model\CountOfProcessing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RequestNewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestNew::class);
    }

    public function findCountOfProcessingBySystemUser(SystemUser $user): CountOfProcessing
    {
        $qb = $this->createQueryBuilder('rn');
        $qb
            ->select('sum(1) processing')
            ->addSelect('count(rn) total_processing')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->gt('rn.createdAt', 'date_sub(now(), 1, \'minute\')'),
                    $qb->expr()->eq('rn.status', 0),
                    $qb->expr()->eq('rn.userId', ':userId'),
                )
            )
            ->setParameter('userId', $user->getId());

        $result = $qb->getQuery()->getOneOrNullResult();

        return new CountOfProcessing(
            processing: (int) ($result['processing'] ?? 0),
            totalProcessing: (int) ($result['total_processing'] ?? 0),
        );
    }
}
