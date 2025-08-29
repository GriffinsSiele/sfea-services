<?php

declare(strict_types=1);

namespace App\Tests\Component\Bulk;

use App\Component\Bulk\Guesser;
use App\Contract\ScalarType;
use App\Model\Scalar;
use App\Model\ScalarDefinition;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GuesserTest extends KernelTestCase
{
    private Guesser $guesser;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->guesser = self::getContainer()->get(Guesser::class);
    }

    public function testNameIsValid(): void
    {
        $scalar = new Scalar('Сергей');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::NAME, $scalar->getType());
    }

    public function testSurnameIsValid(): void
    {
        $scalar = new Scalar('Коденцов');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::SURNAME, $scalar->getType());
    }

    public function testPatronymicIsValid(): void
    {
        $scalar = new Scalar('Александрович');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::PATRONYMIC, $scalar->getType());
    }

    public function testSurnameNamePatronymicIsValid(): void
    {
        $scalar = new Scalar('Коденцов Сергей Александрович');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::SURNAME_NAME_PATRONYMIC, $scalar->getType());
    }

    public function testNamePatronymicSurnameIsValid(): void
    {
        $scalar = new Scalar('Сергей Александрович Коденцов');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::NAME_PATRONYMIC_SURNAME, $scalar->getType());
    }

    public function testBirthdayIsValid(): void
    {
        $scalar = new Scalar('23.02.1989');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::BIRTHDAY, $scalar->getType());
    }

    public function testINNPersonIsValid(): void
    {
        $scalar = new Scalar('503610885804');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::INN, $scalar->getType());
    }

    public function testINNOrgIsValid(): void
    {
        $scalar = new Scalar('7743094450');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::INN, $scalar->getType());
    }

    public function testPassportSeriesIsValid(): void
    {
        $scalar = new Scalar('4611');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::RUSSIAN_PASSPORT_SERIES, $scalar->getType());
    }

    public function testPassportNumberIsValid(): void
    {
        $scalar = new Scalar('000000');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::RUSSIAN_PASSPORT_NUMBER, $scalar->getType());
    }

    public function testPassportIsValid(): void
    {
        $scalar = new Scalar('4611000000');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::RUSSIAN_PASSPORT, $scalar->getType());
    }

    public function testRegionIsValid(): void
    {
        $scalar = new Scalar('77');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::RUSSIAN_REGION, $scalar->getType());
    }

    public function testPhoneIsValid(): void
    {
        $scalar = new Scalar('79772776278');

        $this->guesser->guess($scalar);

        self::assertTrue($scalar->isGuessed());
        self::assertSame(ScalarType::PHONE, $scalar->getType());
    }

    public function testMany(): void
    {
        $faker = Factory::create('ru_RU');

        /** @var array<scalar[]> $rows */
        $rows = [];

        for ($i = 0; $i < 100; ++$i) {
            $rows[] = [
                new Scalar(\uniqid('guarantied unique value', true)),
                new Scalar($faker->firstName),
                new Scalar($faker->lastName),
                new Scalar($faker->dateTimeBetween('-30 years', '-18 years')->format('d.m.Y')),
                new Scalar($faker->phoneNumber),
                new Scalar($faker->randomElement(['male', 'female'])),
            ];
        }

        $definitions = \iterator_to_array($this->guesser->guessMany($rows));

        self::assertCount(6, $definitions);

        $getColumnDefinition = static function (int $i) use ($definitions): ScalarDefinition {
            $slice = \array_filter($definitions, static fn (ScalarDefinition $definition) => $i === $definition->getNumber());

            self::assertCount(1, $slice);

            return \reset($slice);
        };

        self::assertTrue($getColumnDefinition(0)->isUnique());
        self::assertEquals(ScalarType::UNKNOWN, $getColumnDefinition(0)->getType());
        self::assertEquals(ScalarType::NAME, $getColumnDefinition(1)->getType());
        self::assertEquals(ScalarType::SURNAME, $getColumnDefinition(2)->getType());
        self::assertEquals(ScalarType::BIRTHDAY, $getColumnDefinition(3)->getType());
        self::assertEquals(ScalarType::PHONE, $getColumnDefinition(4)->getType());
        self::assertFalse($getColumnDefinition(5)->isUnique());
        self::assertEquals(ScalarType::UNKNOWN, $getColumnDefinition(5)->getType());
    }
}
