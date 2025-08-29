<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Birthday extends Constraint
{
    public const BIRTHDAY_ERROR = '4f193d85-f166-4a7c-941a-42292db05b53';

    protected const ERROR_NAMES = [
        self::BIRTHDAY_ERROR => 'INN_ERROR',
    ];

    public string $message = 'Date is not a birthday adult human';

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
