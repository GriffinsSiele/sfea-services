<?php

declare(strict_types=1);

namespace App\Component\Bulk;

use App\Contract\FastValidatorInterface;
use App\Contract\ScalarType;
use App\Model\Scalar as AppScalar;
use App\Model\ScalarDefinition;
use App\Validator\Constraint\BirthdayValidator;
use App\Validator\Constraint\INNValidator;
use App\Validator\Constraint\NamePatronymicSurnameValidator;
use App\Validator\Constraint\NameValidator;
use App\Validator\Constraint\PatronymicValidator;
use App\Validator\Constraint\PhoneValidator;
use App\Validator\Constraint\RussianPassportNumberValidator;
use App\Validator\Constraint\RussianPassportSeriesValidator;
use App\Validator\Constraint\RussianPassportValidator;
use App\Validator\Constraint\RussianRegionValidator;
use App\Validator\Constraint\SurnameNamePatronymicValidator;
use App\Validator\Constraint\SurnameValidator;
use Co\WaitGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Swoole\Table;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Co\defer;
use function Co\go;
use function Co\run;

class Guesser implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array<ScalarType, FastValidatorInterface>
     */
    private readonly array $constraints;

    public function __construct(
        private readonly ValidatorInterface $validator,

        private readonly BirthdayValidator $birthdayValidator,
        private readonly INNValidator $innValidator,
        private readonly NamePatronymicSurnameValidator $namePatronymicSurnameValidator,
        private readonly NameValidator $nameValidator,
        private readonly PatronymicValidator $patronymicValidator,
        private readonly PhoneValidator $phoneValidator,
        private readonly RussianPassportNumberValidator $russianPassportNumberValidator,
        private readonly RussianPassportSeriesValidator $russianPassportSeriesValidator,
        private readonly RussianPassportValidator $russianPassportValidator,
        private readonly RussianRegionValidator $russianRegionValidator,
        private readonly SurnameNamePatronymicValidator $surnameNamePatronymicValidator,
        private readonly SurnameValidator $surnameValidator,
    ) {
        $this->constraints = [
            [ScalarType::RUSSIAN_REGION, $this->russianRegionValidator],
            [ScalarType::RUSSIAN_PASSPORT_SERIES, $this->russianPassportSeriesValidator],
            [ScalarType::RUSSIAN_PASSPORT_NUMBER, $this->russianPassportNumberValidator],
            [ScalarType::RUSSIAN_PASSPORT, $this->russianPassportValidator],
            [ScalarType::BIRTHDAY, $this->birthdayValidator],
            [ScalarType::PATRONYMIC, $this->patronymicValidator],
            [ScalarType::SURNAME, $this->surnameValidator],
            [ScalarType::NAME, $this->nameValidator],
            [ScalarType::SURNAME_NAME_PATRONYMIC, $this->surnameNamePatronymicValidator],
            [ScalarType::NAME_PATRONYMIC_SURNAME, $this->namePatronymicSurnameValidator],
            [ScalarType::INN, $this->innValidator],
            [ScalarType::PHONE, $this->phoneValidator],
            [
                ScalarType::EMAIL,
                new class() implements FastValidatorInterface {
                    public static function isValid(?string $value, array $context = null): bool
                    {
                        return false !== \filter_var($value, \FILTER_VALIDATE_EMAIL);
                    }
                },
            ],
        ];
    }

    /**
     * @param array<AppScalar[]> $rows
     *
     * @return ScalarDefinition[]|Collection
     */
    public function guessMany(array $rows): Collection
    {
        $this->logger->debug('Guess many start');

        $res = new ArrayCollection();

        run(function () use (&$rows, &$res): void {
            $start = \microtime(true);

            $rowsTable = new Table(\count($rows) * \count($rows[0]), 1.0);
            $rowsTable->column('type', Table::TYPE_STRING, 32);
            $rowsTable->create();

            $usageTable = new Table(\count($rows[0]), 1.0);
            $usageTable->column('count', Table::TYPE_INT, 32);
            $usageTable->create();

            $resTable = new Table(\count($rows[0]), 1.0);
            $resTable->column('type', Table::TYPE_STRING, 32);
            $resTable->create();

            $group = new WaitGroup();

            for ($i = 0, $m = \count($rows); $i < $m; ++$i) {
                for ($j = 0, $n = \count($rows[$i]); $j < $n; ++$j) {
                    $group->add();

                    go(function (int $i, int $j) use (&$rows, &$rowsTable, &$usageTable, &$group): void {
                        defer(static fn () => $group->done());

                        $scalar = $rows[$i][$j];

                        $this->guess($scalar);

                        $scalarTypeVal = $scalar->getType()->value;

                        $rowsTable->set(\sprintf('%d|%d', $i, $j), ['type' => $scalarTypeVal]);
                        $usageTable->incr(\sprintf('%d|%s', $j, $scalarTypeVal), 'count');
                    }, $i, $j);
                }
            }

            $group->wait();

            $this->logger->debug('Guess many at times', [
                'duration' => (\microtime(true) - $start).'s',
            ]);

            $start = \microtime(true);

            $group->add();

            go(static function () use (&$rowsTable, &$rows, &$group): void {
                defer(static fn () => $group->done());

                foreach ($rowsTable as $key => $item) {
                    [$i, $j] = \sscanf($key, '%d|%d');

                    $rows[$i][$j]->setType(ScalarType::from($item['type']));
                }
            });

            $group->add();

            go(function () use (&$resTable, &$usageTable, &$rows, &$group): void {
                defer(static fn () => $group->done());

                $stats = [];

                foreach ($usageTable as $key => $item) {
                    [$j, $scalarTypeVal] = \sscanf($key, '%d|%s');
                    $stats[$j][$scalarTypeVal] = $item['count'];
                }

                foreach ($stats as $j => $stat) {
                    \arsort($stat);

                    $type = \array_key_first($stat);

                    if ($type === ScalarType::UNKNOWN->value) {
                        foreach ($stat as $k => $v) {
                            if ($k === ScalarType::UNKNOWN->value) {
                                continue;
                            }

                            if ($v > \count($rows) / 3) {
                                $type = $k;

                                $this->logger->debug('Use next key instead of primary undefined by potential probability', [
                                    'next_key' => $k,
                                ]);

                                break;
                            }
                        }
                    }

                    $resTable->set(\sprintf('%d', $j), ['type' => $type]);
                }
            });

            $group->wait();

            foreach ($resTable as $key => $item) {
                $res->add(
                    new ScalarDefinition(
                        ScalarType::tryFrom($item['type']),
                        (int) $key,
                    ),
                );
            }

            $this->logger->debug('Set up rows scalar types', [
                'duration' => (\microtime(true) - $start).'s',
            ]);
        });

        $this->logger->debug('Call garbage collector');

        \gc_collect_cycles();

        return $res;
    }

    public function guess(AppScalar $scalar): void
    {
        foreach ($this->constraints as [$scalarType, $validator]) {
            \assert($validator instanceof FastValidatorInterface);

            if (!$validator->isValid($scalar->getValue())) {
                continue;
            }

            $scalar
                ->setType($scalarType)
                ->setGuessed(true);

            break;
        }
    }
}
