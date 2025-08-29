<?php

declare(strict_types=1);

namespace App\Utils\Legacy;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LoggerUtil
{
    private string $logsDir;

    public function __construct(
        ParameterBagInterface $parameterBag,
    ) {
        $this->logsDir = $parameterBag->get('kernel.logs_dir');
    }

    public function log(string $destination, mixed $object): void
    {
        $path = $this->destination($destination);

        \file_put_contents($path, $object);
    }

    public function load(string $destination): ?string
    {
        $path = $this->destination($destination);

        if (!\file_exists($path)) {
            return null;
        }

        return \file_get_contents($path);
    }

    public function rename(string $from, string $to): void
    {
        $from = $this->destination($from);
        $to = $this->destination($to);

        \rename($from, $to);
    }

    private function destination(string $destination): string
    {
        $destination = \ltrim($destination, '/');
        $destination = \preg_replace('~^/?(?:logs?)?/~', '', $destination);
        $path = \rtrim($this->logsDir, '/').'/'.\ltrim($destination, '/');
        $dirname = \dirname($path);

        if (!\is_dir($dirname)) {
            \mkdir($dirname, 0755, true);
        }

        return $path;
    }
}
