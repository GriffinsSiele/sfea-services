<?php

declare(strict_types=1);

namespace App\Component\Bulk\Command;

use App\Entity\Bulk;
use App\Entity\SystemUser;
use App\MessageHandler\ConfirmBulkMessageHandler;
use App\Repository\SystemUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand('app:bulk:upload')]
class UploadBulkCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(
        private readonly ConfirmBulkMessageHandler $confirmBulkMessageHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
        private readonly SystemUserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED)
            ->addArgument('filename', InputArgument::REQUIRED)
            ->addOption('source', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $user = $this->userRepository->findOneByLogin($username);

        if (null === $user) {
            $io->error(\sprintf('User "%s" not found', $username));

            return self::FAILURE;
        }

        $filename = $input->getArgument('filename');
        $sources = $input->getOption('source');

        try {
            $bulk = $this->createBulk($user, $filename, $sources);
        } catch (\Throwable $e) {
            $io->error(\sprintf('Cannot create bulk: %s', $e->getMessage()));

            return self::FAILURE;
        }

        $io->success(\sprintf('Bulk "%s" was created', $bulk->getId()));

        return self::SUCCESS;
    }

    private function createBulk(SystemUser $user, string $filename, array $sources): Bulk
    {
        if (!\file_exists($filename)) {
            throw new \RuntimeException(\sprintf('File "%s" is unreachable', $filename));
        }

        $tmp = \tempnam(\sys_get_temp_dir(), \md5(__CLASS__));

        \copy($filename, $tmp);

        $file = new UploadedFile(\realpath($tmp), \pathinfo($tmp, \PATHINFO_FILENAME), test: true);
        $originalFilename = \pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.\uniqid($user->getUserIdentifier(), true).'.'.$file->guessExtension();

        $file->move($this->container->getParameter('app.bulk_path'), $newFilename);

        $bulk = (new Bulk())
            ->setCreatedAt(new \DateTimeImmutable())
            ->setCreatedDate(new \DateTimeImmutable())
            ->setUser($user)
            ->setIp('127.0.0.1')
            ->setFilename($newFilename)
            ->setRecursive(0)
            ->setStatus(Bulk::STATUS_WAIT)
            ->setSources(\implode(',', $sources));

        $this->entityManager->persist($bulk);
        $this->entityManager->flush();

        return $bulk;
    }
}
