<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Component\IKAO\IKAONormalizer;
use App\Contract\FastValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PatronymicValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Patronymic) {
            throw new UnexpectedTypeException($constraint, Patronymic::class);
        }

        if (!self::isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(Patronymic::FORMAT_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        if (IKAONormalizer::supportsDenormalization($value)) {
            $value = IKAONormalizer::denormalize($value);
        }

        if (!\preg_match('~^[а-яё]+(вич|вна|чна|ьич)$~ui', $value)) {
            return false;
        }

        return true;
    }
}
