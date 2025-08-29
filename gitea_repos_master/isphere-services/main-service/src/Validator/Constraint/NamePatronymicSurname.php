<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class NamePatronymicSurname extends Constraint
{
    public const FORMAT_ERROR = '34ecc365-70f7-4003-b1be-eba95dc5a97f';

    protected const ERROR_NAMES = [
        self::FORMAT_ERROR => 'FORMAT_ERROR',
    ];

    public string $message = 'Invalid name, patronymic and surname combination format';

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
