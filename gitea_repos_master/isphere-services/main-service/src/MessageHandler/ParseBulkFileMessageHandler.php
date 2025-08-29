<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Component\Bulk\Parser;
use App\Entity\Bulk;
use App\Message\ParseBulkFileMessage;
use App\Repository\BulkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ParseBulkFileMessageHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BulkRepository $bulkRepository,
        private readonly Parser $parser,
    ) {
    }

    public function __invoke(ParseBulkFileMessage $message): void
    {
        $bulk = $this->bulkRepository->find($message->getBulkId());
        if (!$bulk) {
            $this->logger->error('Bulk not found by id', [
                'id' => $message->getBulkId(),
            ]);

            return;
        }

        $this->parser->parse($bulk);

        $bulk
            ->setTotalRows(\count($bulk->getRows()))
            ->setStatus(Bulk::STATUS_WAIT_FOR_CONFIRMATION);

        $this->entityManager->persist($bulk);
        $this->entityManager->flush();
    }
}
