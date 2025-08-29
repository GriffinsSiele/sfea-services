<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Contract\FastValidatorInterface;
use App\Form\Type\RussianRegionType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RussianRegionValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RussianRegion) {
            throw new UnexpectedTypeException($constraint, RussianRegion::class);
        }

        if (!self::isValid($value)) {
            $this->context->buildViolation($constraint->notFoundError)
                ->setCode(RussianRegion::NOT_FOUND_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        if (!\is_string($value)) {
            return false;
        }

        return \preg_match('~^\d{2}$~', $value)
            && \in_array($value, RussianRegionType::DEFAULT_CHOICES, true);
    }
}
