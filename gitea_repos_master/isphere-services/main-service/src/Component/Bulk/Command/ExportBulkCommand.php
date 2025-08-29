<?php

declare(strict_types=1);

namespace App\Component\Bulk\Command;

use App\Component\Bulk\Printer;
use App\Component\Bulk\Renderer;
use App\Message\GenerateBulkFileMessage;
use App\MessageHandler\GenerateBulkFileMessageHandler;
use App\Repository\BulkRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:bulk:export')]
class ExportBulkCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly BulkRepository $bulkRepository,
        private readonly GenerateBulkFileMessageHandler $generateBulkFileMessageHandler,
        private readonly Printer $printer,
        private readonly Renderer $renderer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED)
            ->addOption('format', mode: InputOption::VALUE_REQUIRED);
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

        $format = $input->getOption('format');

        if (null === $format) {
            $renderingData = $this->renderer->render($bulk);

            foreach ($renderingData->getHeaders() as $sourceCode => $sourceHeaders) {
                $table = $io->createTable();
                $table->setHeaderTitle($sourceCode);
                $table->setHeaders($sourceHeaders);
                $table->setRows($renderingData->getRows()[$sourceCode]);
                $table->render();
            }
        } elseif ('xlsx' === $format) {
            $message = (new GenerateBulkFileMessage())
                ->setBulkId($bulk->getId());

            $this->generateBulkFileMessageHandler->__invoke($message);
        }

        return self::SUCCESS;
    }
}
