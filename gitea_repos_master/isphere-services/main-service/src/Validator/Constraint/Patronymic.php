<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Patronymic extends Constraint
{
    public const FORMAT_ERROR = '7fa3a787-4b4a-4bf1-8bd4-d3f03d373326';

    protected const ERROR_NAMES = [
        self::FORMAT_ERROR => 'FORMAT_ERROR',
    ];

    public string $message = 'Invalid patronymic format';

    public function __construct(string $message = null, ...$rest)
    {
        parent::__construct(...$rest);

        $this->message = $message ?? $this->message;
    }

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
