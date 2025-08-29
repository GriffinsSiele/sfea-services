<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class INN extends Constraint
{
    public const LEN_ERROR = '6b74205f-da1e-4119-a019-d32b879404a9';
    public const PERSON_ERROR = 'cb4c00fc-8546-4763-be2c-084218c4282e';
    public const ORG_ERROR = '3ff9c19d-9e0c-41a0-b0b1-6d1454562845';

    protected const ERROR_NAMES = [
        self::LEN_ERROR => 'LEN_ERROR',
        self::PERSON_ERROR => 'PERSON_ERROR',
        self::ORG_ERROR => 'ORG_ERROR',
    ];

    public string $lenError = 'This value is not an INN';
    public string $personError = 'This value is not a person INN';
    public bool $person = false;
    public string $orgError = 'This value is not an organization INN';
    public bool $org = false;

    public function __construct(
        bool $person = null,
        bool $org = null,
        string $lenError = null,
        string $personError = null,
        string $orgError = null,
        ...$rest,
    ) {
        parent::__construct(...$rest);

        $this->person = $person ?? $this->person;
        $this->org = $org ?? $this->org;

        if (!$this->person && !$this->org) {
            throw new MissingOptionsException(\sprintf('Either option "person" or "org" must be given for constraint "%s".', __CLASS__), ['person', 'org']);
        }

        $this->lenError = $lenError ?? $this->lenError;
        $this->personError = $personError ?? $this->personError;
        $this->orgError = $orgError ?? $this->orgError;
    }

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
