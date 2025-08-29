<?php

declare(strict_types=1);

namespace App\Command;

use App\Utils\Legacy\CookieUtilStatic;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:replace-directly-cookies', hidden: true)]
class ReplaceDirectlyCookiesCommand extends Command
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
            $methods = \implode('|', \get_class_methods(CookieUtilStatic::class));
            $content = \preg_replace('~\\\\?('.$methods.')\(~m', '\App\Utils\Legacy\CookieUtilStatic::$1(', $content);

            \file_put_contents($file, $content);

            $io->info(\sprintf('%s was updated', $file));
        }

        return self::SUCCESS;
    }
}
