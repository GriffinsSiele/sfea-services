<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RussianPassportSeries extends Constraint
{
    public const LEN_ERROR = 'ae3caa20-5a4d-4f0a-b1d3-92419d62d637';
    public const OKATO_ERROR = 'dbae3aef-ac65-4e20-979c-3dc12ef15e58';
    public const YEAR_ERROR = 'aea7fd55-ef1d-4307-91d3-04f0c58d93c7';

    protected const ERROR_NAMES = [
        self::LEN_ERROR => 'LEN_ERROR',
        self::OKATO_ERROR => 'OKATO_ERROR',
        self::YEAR_ERROR => 'YEAR_ERROR',
    ];

    public string $lenMessage = 'This value should be a valid regex';
    public string $okatoMessage = 'The OKATO component is not a valid';
    public string $yearMessage = 'The year is restricted for russian passport number';

    public function __construct(
        string $lenMessage = null,
        string $okatoMessage = null,
        string $yearMessage = null,
        ...$rest,
    ) {
        parent::__construct(...$rest);

        $this->lenMessage = $lenMessage ?? $this->lenMessage;
        $this->okatoMessage = $okatoMessage ?? $this->okatoMessage;
        $this->yearMessage = $yearMessage ?? $this->yearMessage;
    }

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
