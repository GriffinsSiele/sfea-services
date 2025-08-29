<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Contract\FastValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RussianPassportSeriesValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RussianPassportSeries) {
            throw new UnexpectedTypeException($constraint, RussianPassportSeries::class);
        }

        if (!self::isValid($value)) {
            $this->context->buildViolation($constraint->lenMessage)
                ->setCode(RussianPassportSeries::LEN_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        if (!\is_string($value)) {
            return false;
        }

        if (!\preg_match('~^(\d{2})(\d{2})$~', $value, $m)) {
            return false;
        }

        $year = $m[2];
        $yearNum = (int) \ltrim($year, '0');
        $currentYearShort = \date('y');
        $currentYearShortNum = (int) \ltrim($currentYearShort, '0');

        if ($yearNum > $currentYearShortNum) {
            $year = (int) ('19'.$year);
        } else {
            $year = (int) ('20'.$year);
        }

        $maxYear = (int) \date('Y', \strtotime('+3 years'));
        $minYear = (int) \date('Y', \strtotime('-45 years -3 years'));

        return !($year < $minYear || $year > $maxYear);
    }
}
