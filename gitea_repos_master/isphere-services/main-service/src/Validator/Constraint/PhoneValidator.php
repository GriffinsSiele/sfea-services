<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Contract\FastValidatorInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PhoneValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Phone) {
            throw new UnexpectedTypeException($constraint, Phone::class);
        }

        $value = \preg_replace('~\D+~', '', $value);
        $atLeastOneValid = false;
        $phoneUtil = PhoneNumberUtil::getInstance();

        foreach ($constraint->regions as $region) {
            try {
                $phoneNumber = $phoneUtil->parse($value, $region);
            } catch (NumberParseException $e) {
                $this->context->buildViolation($constraint->parseError)
                    ->setCode(Phone::PARSE_ERROR)
                    ->addViolation();

                break;
            }

            if ($phoneUtil->isValidNumberForRegion($phoneNumber, $region)) {
                $atLeastOneValid = true;

                break;
            }
        }

        if (!$atLeastOneValid) {
            $this->context->buildViolation($constraint->formatError)
                ->setCode(Phone::FORMAT_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($value, 'RU');
        } catch (NumberParseException) {
            return false;
        }

        if (null === $phoneNumber) {
            return false;
        }

        return $phoneUtil->isValidNumberForRegion($phoneNumber, 'RU');
    }
}
