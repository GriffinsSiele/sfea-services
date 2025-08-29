<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RussianPassportNumber extends Constraint
{
    public const LEN_ERROR = '06badd17-94c5-4d3a-a7e9-98d736390bb8';

    protected const ERROR_NAMES = [
        self::LEN_ERROR => 'LEN_ERROR',
    ];

    public string $lenMessage = 'This value is not a russian passport number';

    public function __construct(
        string $lenMessage = null,
        ...$rest,
    ) {
        parent::__construct(...$rest);

        $this->lenMessage = $lenMessage ?? $this->lenMessage;
    }

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
