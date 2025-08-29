<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Component\Bulk\Printer;
use App\Entity\Bulk;
use App\Message\GenerateBulkFileMessage;
use App\Repository\BulkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

#[AsMessageHandler]
class GenerateBulkFileMessageHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BulkRepository $bulkRepository,
        private readonly MimeTypeGuesserInterface $mimeTypeGuesser,
        private readonly Printer $printer,
        private readonly string $bulkpath,
    ) {
    }

    public function __invoke(GenerateBulkFileMessage $message): void
    {
        $bulk = $this->bulkRepository->find($message->getBulkId());

        if (!$bulk) {
            $this->logger->error('Bulk not found by id', [
                'id' => $message->getBulkId(),
            ]);

            return;
        }

        $originalStatus = $bulk->getStatus();

        $bulk->setStatus(Bulk::STATUS_GENERATING_FILE);

        $this->entityManager->persist($bulk);
        $this->entityManager->flush();

        $file = $this->printer->printBulk($bulk);

        if (null === $file) {
            $this->logger->error('Could not create bulk file', [
                'bulk_id' => $bulk->getId(),
            ]);

            $bulk->setStatus(Bulk::STATUS_NOT_COMPLETED);

            $this->entityManager->persist($bulk);
            $this->entityManager->flush();

            return;
        }

        $filename = \rtrim($this->bulkpath, '/').'/'.$bulk->getId().'_res.'.$file->getClientOriginalExtension();

        \copy($file->getPathname(), $filename);

        $bulk
            ->setResultFilename($filename)
            ->setStatus($originalStatus);

        $this->entityManager->persist($bulk);
        $this->entityManager->flush();
    }
}
