<?php

declare(strict_types=1);

namespace App\Component\Bulk\Command;

use App\Entity\Bulk;
use App\Message\ConfirmBulkMessage;
use App\MessageHandler\ConfirmBulkMessageHandler;
use App\Repository\BulkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:bulk:confirm')]
class ConfirmBulkCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly BulkRepository $bulkRepository,
        private readonly ConfirmBulkMessageHandler $confirmBulkMessageHandler,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getArgument('id');
        $bulk = $this->bulkRepository->find($id);

        if (null === $bulk) {
            $io->error('Bulk not found');

            return self::FAILURE;
        }

        $bulk->setStatus(Bulk::STATUS_WAIT_CONFIRMED);

        $this->entityManager->persist($bulk);
        $this->entityManager->flush();

        $message = (new ConfirmBulkMessage())
            ->setBulkId($bulk->getId());

        $this->confirmBulkMessageHandler->__invoke($message);

        return self::SUCCESS;
    }
}
