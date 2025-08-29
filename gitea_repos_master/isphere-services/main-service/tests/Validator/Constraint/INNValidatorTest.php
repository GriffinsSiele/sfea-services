<?php

declare(strict_types=1);

namespace App\Tests\Validator\Constraint;

use App\Validator\Constraint\INN;
use App\Validator\Constraint\INNValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class INNValidatorTest extends ConstraintValidatorTestCase
{
    public function testPersonIsValid(): void
    {
        $constraint = new INN(person: true);
        $this->validator->validate('503610885804', $constraint);

        $this->assertNoViolation();
    }

    public function testOrgIsValid(): void
    {
        $constraint = new INN(org: true);
        $this->validator->validate('7743094450', $constraint);

        $this->assertNoViolation();
    }

    public function testPersonWhenOrgArgIsSetInvalid(): void
    {
        $constraint = new INN(person: true);
        $this->validator->validate('7743094450', $constraint);

        $this->buildViolation($constraint->personError)
            ->setCode(INN::PERSON_ERROR)
            ->assertRaised();
    }

    public function testOrgWhenPersonArgIsSetInvalid(): void
    {
        $constraint = new INN(org: true);
        $this->validator->validate('503610885804', $constraint);

        $this->buildViolation($constraint->orgError)
            ->setCode(INN::ORG_ERROR)
            ->assertRaised();
    }

    public function testBothValid(): void
    {
        $constraint = new INN(person: true, org: true);
        $this->validator->validate('503610885804', $constraint);

        $this->assertNoViolation();

        $this->validator->validate('7743094450', $constraint);

        $this->assertNoViolation();
    }

    public function testInvalid(): void
    {
        $constraint = new INN(person: true, org: true);
        $this->validator->validate('111', $constraint);

        $this->buildViolation($constraint->lenError)
            ->setCode(INN::LEN_ERROR)
            ->assertRaised();
    }

    public function testInvalid2(): void
    {
        $constraint = new INN(person: true, org: true);
        $this->validator->validate('1000100', $constraint);

        $this->buildViolation($constraint->lenError)
            ->setCode(INN::LEN_ERROR)
            ->assertRaised();
    }

    public function testInvalid3(): void
    {
        $constraint = new INN(person: true, org: true);
        $this->validator->validate('male', $constraint);

        $this->buildViolation($constraint->lenError)
            ->setCode(INN::LEN_ERROR)
            ->assertRaised();
    }

    public function testInvalid10Symbols(): void
    {
        $constraint = new INN(person: true, org: true);
        $this->validator->validate('4611000000', $constraint);

        $this->buildViolation($constraint->lenError)
            ->setCode(INN::LEN_ERROR)
            ->assertRaised();
    }

    protected function createValidator(): ConstraintValidator
    {
        return new INNValidator();
    }
}
