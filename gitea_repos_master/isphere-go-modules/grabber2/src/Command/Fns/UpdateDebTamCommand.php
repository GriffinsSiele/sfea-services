<?php

declare(strict_types=1);

namespace App\Command\Fns;

use App\Entity\Fns\DebTam as DebTamEntity;
use App\Entity\Fns\DebTamRecord;
use App\Model\Fns\DebTam;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(name: 'fns:debtam:update')]
class UpdateDebTamCommand extends AbstractFnsCommand
{
    private const DEFAULT_URL = 'https://www.nalog.ru/opendata/7707329152-debtam/';

    public function __construct(
        private readonly EntityManagerInterface $defaultEntityManager,
        private readonly SerializerInterface $serializer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('url', mode: InputOption::VALUE_REQUIRED, default: self::DEFAULT_URL)
            ->addOption('use-cache', mode: InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $url = $input->getOption('url');
        $useCache = $input->getOption('use-cache');

        $content = $this->fetchPage($io, $url);
        $source = $this->fetchSource($io, $content, 'zip', $useCache);

        $debTamTable = $this->defaultEntityManager->getClassMetadata(DebTamEntity::class);
        $debTamSchema = $debTamTable->getSchemaName();
        $debTamTableName = $debTamTable->getTableName();

        $debTamRecordTable = $this->defaultEntityManager->getClassMetadata(DebTamRecord::class);
        $debTamRecordSchema = $debTamRecordTable->getSchemaName();
        $debTamRecordTableName = $debTamRecordTable->getTableName();

        $defaultConnection = $this->defaultEntityManager->getConnection();
        $defaultConnection->executeStatement(\sprintf('truncate table %s.%s cascade', $debTamSchema, $debTamTableName));

        $io->progressStart();

        $recordId = 1;

        foreach ($io->progressIterate($this->forEveryFileInArchive($source, 'xml')) as $serialized) {
            /** @var DebTam $model */
            $model = $this->serializer->deserialize($serialized, DebTam::class, XmlEncoder::FORMAT);

            /** @var array<string, mixed> $primary */
            $primary = [];

            /** @var array<string, mixed> $secondary */
            $secondary = [];

            foreach ($model->getDocuments() as $document) {
                $primary[] = [
                    'id' => $document->getId(),
                    'inn' => $document->getSubject()?->getInn(),
                    'created_at' => $document->getCreatedAt()?->format('Y-m-d'),
                    'updated_at' => $document->getUpdatedAt()?->format('Y-m-d'),
                ];

                foreach ($document->getUnderpayments() as $underpayment) {
                    $secondary[] = [
                        'id' => $recordId,
                        'debtam_id' => $document->getId(),
                        'name' => $underpayment->getName(),
                        'tax' => $underpayment->getTax(),
                        'penalty' => $underpayment->getPenalty(),
                        'fine' => $underpayment->getFine(),
                        'arrears' => $underpayment->getArrears(),
                    ];

                    ++$recordId;
                }
            }

            if (false === $this->pgInsertByCopy(
                $defaultConnection->getNativeConnection(),
                $debTamSchema,
                $debTamTableName,
                ['id', 'inn', 'created_at', 'updated_at'],
                $primary,
            )) {
                throw new \RuntimeException('Failed to copy from primary');
            }

            if (false === $this->pgInsertByCopy(
                $defaultConnection->getNativeConnection(),
                $debTamRecordSchema,
                $debTamRecordTableName,
                ['id', 'debtam_id', 'name', 'tax', 'penalty', 'fine', 'arrears'],
                $secondary,
            )) {
                throw new \RuntimeException('Failed to copy from secondary');
            }
        }

        $io->progressFinish();

        return self::SUCCESS;
    }
}
