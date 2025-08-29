<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Contract\FastValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RussianPassportNumberValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RussianPassportNumber) {
            throw new UnexpectedTypeException($constraint, RussianPassportNumber::class);
        }

        if (!self::isValid($value)) {
            $this->context->buildViolation($constraint->lenMessage)
                ->setCode(RussianPassportNumber::LEN_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        if (!\preg_match('~^\d{6}$~', $value)) {
            return false;
        }

        return true;
    }
}
