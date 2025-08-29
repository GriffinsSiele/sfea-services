<?php

declare(strict_types=1);

namespace App\Contract;

interface FastValidatorInterface
{
    public static function isValid(?string $value, array $context = null): bool;
}
