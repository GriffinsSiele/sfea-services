<?php

declare(strict_types=1);

namespace App\Component\OKATO\Command;

use App\Entity\Okato;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:okato:update-database')]
class UpdateOKATODatabaseCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('url', mode: InputOption::VALUE_REQUIRED, default: 'https://rosstat.gov.ru/opendata/list.csv')
            ->addOption('batch_size', mode: InputOption::VALUE_REQUIRED, default: 1000);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $url = $input->getOption('url');
        $fh = \fopen($url, 'r');
        $target = null;
        $io->writeln('Finding actual OKATO URL');
        $io->progressStart();

        while ($line = \fgetcsv($fh)) {
            $io->progressAdvance(1);

            if (\preg_match('~^\d+-okato$~', $line[0])) {
                $target = $line[2];

                break;
            }
        }

        $io->progressFinish();
        \fclose($fh);

        if (null === $target) {
            $io->error('Could not found OKATO URL on '.$url);

            return self::FAILURE;
        }

        $io->success('Found OKATO URL '.$target);

        $url = $target;
        $fh = \fopen($url, 'r');
        $target = null;
        $io->writeln('Finding latest URL');
        $io->progressStart();

        while ($line = \fgetcsv($fh)) {
            $io->progressAdvance(1);

            if (\preg_match('~^data-\d+-structure-\d+\.csv$~', $line[0])) {
                $target = $line[1];

                break;
            }
        }

        $io->progressFinish();
        \fclose($fh);

        if (null === $target) {
            $io->error('Could not found latest URL on '.$url);
        }

        $io->success('Found latest URL '.$target);

        $url = $target;
        $fh = \fopen($url, 'r');
        $io->writeln('Updating database');
        $io->progressStart();

        $this->entityManager->getConnection()->executeStatement('truncate table `okato`');

        $i = 0;
        $batchSize = (int) $input->getOption('batch_size');

        while ($line = \fgetcsv($fh, separator: ';')) {
            $io->progressAdvance(1);

            $line = \array_map(static fn ($v) => \iconv('CP1251', 'UTF-8', $v), $line);

            $okato = (new Okato())
                ->setTer($this->string($line[0]))
                ->setKod1($this->string($line[1]))
                ->setKod2($this->string($line[2]))
                ->setKod3($this->string($line[3]))
                ->setRazdel($this->string($line[4]))
                ->setName1($this->string($line[5]))
                ->setCentrum($this->string($line[6]))
                ->setNomDescr($this->string($line[7]))
                ->setNomAkt($this->int($line[8]))
                ->setStatus($this->int($line[9]))
                ->setDateUtv($this->dateTime($line[10]))
                ->setDateVved($this->dateTime($line[11]));

            $this->entityManager->persist($okato);

            if (0 === ++$i % $batchSize) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->progressFinish();
        \fclose($fh);

        return self::SUCCESS;
    }

    private function string(string $v): ?string
    {
        $v = \trim($v);

        return !empty($v) ? $v : null;
    }

    private function int(string $v): ?int
    {
        if (!\is_numeric($v)) {
            return null;
        }

        if ('' === $v) {
            return null;
        }

        return (int) $v;
    }

    private function dateTime(string $v): ?\DateTimeInterface
    {
        if (empty($v)) {
            return null;
        }

        return new \DateTimeImmutable($v);
    }
}
