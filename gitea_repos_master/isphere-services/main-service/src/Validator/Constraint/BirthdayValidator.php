<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Contract\FastValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BirthdayValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Birthday) {
            throw new UnexpectedTypeException($constraint, Birthday::class);
        }

        if (!self::isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(Birthday::BIRTHDAY_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        if (\is_string($value) && !empty($value)) {
            try {
                $value = new \DateTimeImmutable($value);
            } catch (\Throwable) {
                return false;
            }
        }

        if (!$value instanceof \DateTimeInterface) {
            return false;
        }

        $years = $value->diff(new \DateTimeImmutable())->y;

        return !($years < 18 || $years > 120);
    }
}
