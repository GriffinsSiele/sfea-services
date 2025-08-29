<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Name extends Constraint
{
    public const FORMAT_ERROR = 'e1dcd85b-cb25-4ffe-bb21-0d01bf6b18d3';

    protected const ERROR_NAMES = [
        self::FORMAT_ERROR => 'FORMAT_ERROR',
    ];

    public string $message = 'Invalid name value';

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
