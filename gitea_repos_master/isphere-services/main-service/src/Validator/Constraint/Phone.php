<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Phone extends Constraint
{
    public const FORMAT_ERROR = 'a182cc61-1446-404e-810f-93554dcf49b9';
    public const PARSE_ERROR = 'ff479c8c-4769-41c6-aa8f-072f2f53bc4a';

    protected const ERROR_NAMES = [
        self::FORMAT_ERROR => 'FORMAT_ERROR',
        self::PARSE_ERROR => 'PARSE_ERROR',
    ];

    public string $formatError = 'This value is not allowed for allowed regions';
    public string $parseError = 'This value cannot parse';
    public array $regions = [];

    public function __construct(
        array $regions = null,
        string $formatError = null,
        string $parseError = null,
        ...$rest,
    ) {
        parent::__construct(...$rest);

        $this->regions = $regions ?? $this->regions;

        if (empty($this->regions)) {
            throw new MissingOptionsException(\sprintf('The option "regions" must be given for constraint "%s".', __CLASS__), ['regions']);
        }

        $this->formatError = $formatError ?? $this->formatError;
        $this->parseError = $parseError ?? $this->parseError;
    }

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
