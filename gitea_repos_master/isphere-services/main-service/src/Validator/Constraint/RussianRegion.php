<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RussianRegion extends Constraint
{
    public const NOT_FOUND_ERROR = '5ee96c05-6050-4fc7-a211-3db2b69936fb';

    protected const ERROR_NAMES = [
        self::NOT_FOUND_ERROR => 'NOT_FOUND_ERROR',
    ];

    public string $notFoundError = 'This value is not a russian region code';

    public function __construct(
        string $notFoundError = null,
        ...$rest,
    ) {
        parent::__construct(...$rest);

        $this->notFoundError = $notFoundError ?? $this->notFoundError;
    }

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
