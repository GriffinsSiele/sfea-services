<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:replace-php-auth-variables', hidden: true)]
class ReplacePhpAuthVariablesCommand extends Command
{
    public const NAME = 'app:replace-php-auth-variables';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dir = \dirname(__DIR__, 2).'/templates';
        $files = \glob($dir.'/*.php');

        foreach ($files as $file) {
            $content = \file_get_contents($file);

            if (!\preg_match('~PHP_AUTH_(USER|PW)~m', $content)) {
                continue;
            }

            $content = \preg_replace('~\$_SERVER\[[\'"]PHP_AUTH_USER[\'"]\]~', '$user->getUserIdentifier()', $content);
            $content = \preg_replace('~\$_SERVER\[[\'"]PHP_AUTH_PW[\'"]\]~', '$user->getPassword()', $content);

            \file_put_contents($file, $content);

            $io->info(\sprintf('%s was updated', $file));
        }

        return self::SUCCESS;
    }
}
