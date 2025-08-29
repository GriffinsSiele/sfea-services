<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RussianPassport extends Constraint
{
    public const LEN_ERROR = '1fb2fcac-8188-4230-b1f3-285ebea4900f';

    protected const ERROR_NAMES = [
        self::LEN_ERROR => 'LEN_ERROR',
    ];

    public string $lenMessage = 'This value is not a russian passport';

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
