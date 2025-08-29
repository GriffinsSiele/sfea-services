<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Contract\FastValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RussianPassportValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RussianPassport) {
            throw new UnexpectedTypeException($constraint, RussianPassport::class);
        }

        if (!self::isValid($value)) {
            $this->context->buildViolation($constraint->lenMessage)
                ->setCode(RussianPassport::LEN_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        if (!\preg_match('~^(\d{4})(\d{6})$~', $value, $m)) {
            return false;
        }

        if (PhoneValidator::isValid($value, $context)) {
            return false;
        }

        return RussianPassportSeriesValidator::isValid($m[1])
            && RussianPassportNumberValidator::isValid($m[2]);
    }
}
