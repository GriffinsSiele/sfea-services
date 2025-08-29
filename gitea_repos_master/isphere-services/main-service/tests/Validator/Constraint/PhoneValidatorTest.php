<?php

declare(strict_types=1);

namespace App\Tests\Validator\Constraint;

use App\Validator\Constraint\Phone;
use App\Validator\Constraint\PhoneValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PhoneValidatorTest extends ConstraintValidatorTestCase
{
    public function testRussianIsValid(): void
    {
        $constraint = new Phone(regions: ['RU']);
        $this->validator->validate('79772776278', $constraint);

        $this->assertNoViolation();
    }

    public function testUkrainianIsValid(): void
    {
        $constraint = new Phone(regions: ['UA']);
        $this->validator->validate('380443519999', $constraint);

        $this->assertNoViolation();
    }

    public function testUkrainianForRussianMappingInvalid(): void
    {
        $constraint = new Phone(regions: ['RU']);
        $this->validator->validate('380443519999', $constraint);

        $this->buildViolation($constraint->formatError)
            ->setCode(Phone::FORMAT_ERROR)
            ->assertRaised();
    }

    protected function createValidator(): ConstraintValidator
    {
        return new PhoneValidator();
    }
}
