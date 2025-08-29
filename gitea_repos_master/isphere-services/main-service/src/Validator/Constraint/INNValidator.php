<?php

declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Contract\FastValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class INNValidator extends ConstraintValidator implements FastValidatorInterface
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof INN) {
            throw new UnexpectedTypeException($constraint, INN::class);
        }

        $value = \preg_replace('~\D+~', '', $value);

        $phoneErrors = $this->validator->validate($value, constraints: [
            new Phone(regions: ['RU']),
        ]);

        if (0 === $phoneErrors->count()) {
            $this->context->buildViolation($constraint->lenError)
                ->setCode(INN::LEN_ERROR)
                ->addViolation();

            return;
        }

        $hasPersonError = false;
        $hasOrgError = false;

        if (\preg_match("~^\d{12}$~", $value)) {
            $inn = $value;
            $code11 = (($inn[0] * 7 + $inn[1] * 2 + $inn[2] * 4 + $inn[3] * 10 + $inn[4] * 3 + $inn[5] * 5 + $inn[6] * 9 + $inn[7] * 4 + $inn[8] * 6 + $inn[9] * 8) % 11) % 10;
            $code12 = (($inn[0] * 3 + $inn[1] * 7 + $inn[2] * 2 + $inn[3] * 4 + $inn[4] * 10 + $inn[5] * 3 + $inn[6] * 5 + $inn[7] * 9 + $inn[8] * 4 + $inn[9] * 6 + $inn[10] * 8) % 11) % 10;

            if ($code11 !== (int) $inn[10] || $code12 !== (int) $inn[11]) {
                $hasPersonError = true;
            }
        } else {
            $hasPersonError = true;
        }

        if (\preg_match("~^\d{10}$~", $value)) {
            $inn = $value;
            $code10 = (($inn[0] * 2 + $inn[1] * 4 + $inn[2] * 10 + $inn[3] * 3 + $inn[4] * 5 + $inn[5] * 9 + $inn[6] * 4 + $inn[7] * 6 + $inn[8] * 8) % 11) % 10;

            if ($code10 !== (int) $inn[9]) {
                $hasOrgError = true;
            }
        } else {
            $hasOrgError = true;
        }

        if ($constraint->person && $hasPersonError && !$constraint->org) {
            $this->context->buildViolation($constraint->personError)
                ->setCode(INN::PERSON_ERROR)
                ->addViolation();

            return;
        }

        if ($constraint->org && $hasOrgError && !$constraint->person) {
            $this->context->buildViolation($constraint->orgError)
                ->setCode(INN::ORG_ERROR)
                ->addViolation();

            return;
        }

        if ($constraint->person && $constraint->org && $hasPersonError && $hasOrgError) {
            $this->context->buildViolation($constraint->lenError)
                ->setCode(INN::LEN_ERROR)
                ->addViolation();
        }
    }

    public static function isValid(?string $value, array $context = null): bool
    {
        $isPersonValid = false;
        $isOrgValid = false;

        if (\preg_match("~^\d{12}$~", $value)) {
            $inn = $value;
            $code11 = (($inn[0] * 7 + $inn[1] * 2 + $inn[2] * 4 + $inn[3] * 10 + $inn[4] * 3 + $inn[5] * 5 + $inn[6] * 9 + $inn[7] * 4 + $inn[8] * 6 + $inn[9] * 8) % 11) % 10;
            $code12 = (($inn[0] * 3 + $inn[1] * 7 + $inn[2] * 2 + $inn[3] * 4 + $inn[4] * 10 + $inn[5] * 3 + $inn[6] * 5 + $inn[7] * 9 + $inn[8] * 4 + $inn[9] * 6 + $inn[10] * 8) % 11) % 10;

            if (!($code11 !== (int) $inn[10] || $code12 !== (int) $inn[11])) {
                $isPersonValid = true;
            }
        }

        if (\preg_match("~^\d{10}$~", $value)) {
            $inn = $value;
            $code10 = (($inn[0] * 2 + $inn[1] * 4 + $inn[2] * 10 + $inn[3] * 3 + $inn[4] * 5 + $inn[5] * 9 + $inn[6] * 4 + $inn[7] * 6 + $inn[8] * 8) % 11) % 10;

            if (!($code10 !== (int) $inn[9])) {
                $isOrgValid = true;
            }
        }

        return $isPersonValid || $isOrgValid;
    }
}
