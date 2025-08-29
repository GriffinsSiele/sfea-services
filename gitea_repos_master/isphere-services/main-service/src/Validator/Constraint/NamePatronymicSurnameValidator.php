<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Component\IKAO\IKAONormalizer;
use App\Contract\FastValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NamePatronymicSurnameValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NamePatronymicSurname) {
            throw new UnexpectedTypeException($constraint, NamePatronymicSurname::class);
        }

        if (!self::isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(NamePatronymicSurname::FORMAT_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        if (IKAONormalizer::supportsDenormalization($value)) {
            $value = IKAONormalizer::denormalize($value);
        }

        if (!\preg_match('~^[а-яё]+ [а-яё]+(вич|вна|чна|ьич) [а-яё]+(ова?|ева?|ёва?|ина?|ына?|их|ых|ский|цкий|ская|цкая|юк|ук|нко|дзе|швили|ян|ик|ко|ан)$~ui', $value)) {
            return false;
        }

        return true;
    }
}
