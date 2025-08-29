<?php

declare(strict_types=1);

namespace App\Tests\Validator\Constraint;

use App\Validator\Constraint\Birthday;
use App\Validator\Constraint\BirthdayValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class BirthdayValidatorTest extends ConstraintValidatorTestCase
{
    public function testSuccess(): void
    {
        $this->validator->validate(new \DateTimeImmutable('-20 years'), new Birthday());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function testYoung(Birthday $constraint): void
    {
        $date = new \DateTimeImmutable('-10 years');
        $this->validator->validate($date, $constraint);

        $this->buildViolation('test fail')
            ->setCode(Birthday::BIRTHDAY_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function testOld(Birthday $constraint): void
    {
        $date = new \DateTimeImmutable('-200 years');
        $this->validator->validate($date, $constraint);

        $this->buildViolation('test fail')
            ->setCode(Birthday::BIRTHDAY_ERROR)
            ->assertRaised();
    }

    protected function createValidator(): ConstraintValidator
    {
        return new BirthdayValidator();
    }

    public function provideInvalidConstraints(): iterable
    {
        yield [new Birthday(message: 'test fail')];
    }
}
