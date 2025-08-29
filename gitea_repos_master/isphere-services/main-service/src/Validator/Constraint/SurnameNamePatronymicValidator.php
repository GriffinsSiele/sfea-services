<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Component\IKAO\IKAONormalizer;
use App\Contract\FastValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SurnameNamePatronymicValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SurnameNamePatronymic) {
            throw new UnexpectedTypeException($constraint, SurnameNamePatronymic::class);
        }

        if (!self::isValid($value)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(SurnameNamePatronymic::FORMAT_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        if (IKAONormalizer::supportsDenormalization($value)) {
            $value = IKAONormalizer::denormalize($value);
        }

        if (!\preg_match('~^[а-яё]+(ова?|ева?|ёва?|ина?|ына?|их|ых|ский|цкий|ская|цкая|юк|ук|нко|дзе|швили|ян|ик|ко|ан) [а-яё]+ [а-яё]+(вич|вна|чна|ьич)$~ui', $value)) {
            return false;
        }

        return true;
    }
}
