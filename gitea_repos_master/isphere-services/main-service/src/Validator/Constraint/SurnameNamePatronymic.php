<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SurnameNamePatronymic extends Constraint
{
    public const FORMAT_ERROR = 'c397c6b7-96dc-4195-ad39-c84d183047ee';

    protected const ERROR_NAMES = [
        self::FORMAT_ERROR => 'FORMAT_ERROR',
    ];

    public string $message = 'Invalid surname, name and patronymic combination format';

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
