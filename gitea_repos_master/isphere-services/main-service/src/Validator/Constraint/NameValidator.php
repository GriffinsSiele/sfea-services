<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Component\IKAO\IKAONormalizer;
use App\Contract\FastValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NameValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Name) {
            throw new UnexpectedTypeException($constraint, Name::class);
        }

        if (!self::isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(Name::FORMAT_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        if (IKAONormalizer::supportsDenormalization($value)) {
            $value = IKAONormalizer::denormalize($value);
        }

        return \preg_match('~^[а-яё]+$~ui', $value)
            && !SurnameValidator::isValid($value)
            && !PatronymicValidator::isValid($value);
    }
}
