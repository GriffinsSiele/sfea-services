<?php

declare(strict_types=1);

namespace App\Utils\Legacy;

use App\Kernel;

class LoggerUtilStatic
{
    public static function file_put_contents(
        string $filename,
        mixed $data,
        int $flags = 0,
        mixed $context = null
    ): int|false {
        if (!\str_contains($filename, 'logs/')) {
            return \file_put_contents($filename, $data, $flags, $context);
        }

        $kernel = Kernel::getInstance();
        $logsDir = $kernel->getContainer()->getParameter('kernel.logs_dir');
        $environment = $kernel->getContainer()->getParameter('kernel.environment');
        $filename = \preg_replace('~(?:\./)?logs/~', $logsDir.'/'.$environment.'/', $filename);
        $dir = \dirname($filename);

        if (!\is_dir($dir)) {
            \mkdir($dir, 0755, true);
        }

        return \file_put_contents($filename, $data, $flags, $context);
    }
}
