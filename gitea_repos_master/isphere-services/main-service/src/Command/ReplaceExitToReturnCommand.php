<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:replace-exit-to-return', hidden: true)]
class ReplaceExitToReturnCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dir = \dirname(__DIR__, 2).'/templates';
        $files = \glob($dir.'/*.php');

        foreach ($files as $file) {
            $content = \file_get_contents($file);

            if (!\preg_match('~\bexit;~m', $content)) {
                continue;
            }

            $content = \preg_replace('~\bexit;~m', 'return;', $content);

            \file_put_contents($file, $content);

            $io->info(\sprintf('%s was updated', $file));
        }

        return self::SUCCESS;
    }
}
