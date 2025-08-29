<?php

declare(strict_types=1);

namespace App\Component\Bulk\Command;

use App\Entity\Bulk;
use App\Message\ParseBulkFileMessage;
use App\MessageHandler\ParseBulkFileMessageHandler;
use App\Repository\BulkRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand('app:bulk:parse')]
class ParseBulkCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly BulkRepository $bulkRepository,
        private readonly ParseBulkFileMessageHandler $parseBulkFileMessageHandler,
        private readonly TranslatorInterface $translator,
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

        try {
            $this->parseBulk($bulk);
        } catch (\Throwable $e) {
            $io->error(\sprintf('Cannot parse bulk: %s', $e->getMessage()));

            return self::FAILURE;
        }

        $io->success('File parsed');

        $table = $io->createTable();
        $table->setHeaderTitle('№'.$bulk->getId());
        $headers = [];

        foreach ($bulk->getDefinitionsRepresentative() as $definition) {
            $headers[] = $this->translator->trans($definition->getType()->name, [], 'scalar_types');
        }

        $table->setHeaders($headers);

        foreach ($bulk->getRows() as $scalars) {
            $row = [];

            foreach ($scalars as $scalar) {
                $row[] = $scalar->getValue();
            }

            $table->addRow($row);
        }

        $table->render();

        return self::SUCCESS;
    }

    private function parseBulk(Bulk $bulk): void
    {
        $message = (new ParseBulkFileMessage())
            ->setBulkId($bulk->getId());

        $this->parseBulkFileMessageHandler->__invoke($message);
    }
}
