<?php

declare(strict_types=1);

namespace App\Tests\Validator\Constraint;

use App\Repository\OkatoRepository;
use App\Validator\Constraint\RussianPassportSeries;
use App\Validator\Constraint\RussianPassportSeriesValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RussianPassportSeriesValidatorTest extends ConstraintValidatorTestCase
{
    public function testSuccess(): void
    {
        $this->validator->validate('4611', new RussianPassportSeries());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function testFailYear(RussianPassportSeries $constraint): void
    {
        $this->validator->validate('4650', $constraint);

        $this->buildViolation($constraint->yearMessage)
            ->setCode(RussianPassportSeries::YEAR_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function testFailLen(RussianPassportSeries $constraint): void
    {
        $this->validator->validate('46111', $constraint);

        $this->buildViolation($constraint->lenMessage)
            ->setCode(RussianPassportSeries::LEN_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider provideInvalidConstraints
     */
    public function testFailOkato(RussianPassportSeries $constraint): void
    {
        $this->validator->validate('0911', $constraint);

        $this->buildViolation($constraint->okatoMessage)
            ->setCode(RussianPassportSeries::OKATO_ERROR)
            ->assertRaised();
    }

    protected function createValidator(): ConstraintValidator
    {
        $okatoRepository = $this->getMockBuilder(OkatoRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $okatoRepository->method('existsByTer')
            ->willReturnCallback(static fn ($v): bool => 0 === $v % 2);

        return new RussianPassportSeriesValidator($okatoRepository);
    }

    public function provideInvalidConstraints(): iterable
    {
        yield [new RussianPassportSeries()];
    }
}
