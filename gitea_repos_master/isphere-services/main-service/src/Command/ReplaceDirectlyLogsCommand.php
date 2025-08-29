<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:replace-directly-logs', hidden: true)]
class ReplaceDirectlyLogsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dir = \dirname(__DIR__, 2).'/templates/engine/plugins';
        $files = \glob($dir.'/*.php');

        foreach ($files as $file) {
            if (\str_contains($file, '_old')
                || \str_contains($file, '_new')
                || \str_contains($file, '_v2')
            ) {
                continue;
            }

            $content = \file_get_contents($file);
            $content = \preg_replace('~\\\\?file_put_contents\(~m', '\App\Utils\Legacy\LoggerUtilStatic::file_put_contents(', $content);

            \file_put_contents($file, $content);

            $io->info(\sprintf('%s was updated', $file));
        }

        return self::SUCCESS;
    }
}
