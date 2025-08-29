<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Contract\DuplicateStatsEntityInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

#[AsDoctrineListener(event: Events::postPersist, connection: 'default')]
class DuplicateStatsEntityEventSubscriber implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $statsEntityManager,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $object = $args->getObject();

        if (!$object instanceof DuplicateStatsEntityInterface) {
            return;
        }

        $this->statsEntityManager->persist($object);

        $this->logger->debug('Duplicate stats entity', [
            'entity_class' => $object::class,
        ]);
    }
}
