<?php

declare(strict_types=1);

namespace App\Component\Bulk;

interface ParserInterface
{
    public function isSupportsMimeType(string $mimeType): bool;

    public function parse(string $filename, string $mimeType): array;
}
