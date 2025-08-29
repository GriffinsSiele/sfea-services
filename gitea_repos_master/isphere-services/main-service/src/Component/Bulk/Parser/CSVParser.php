<?php

declare(strict_types=1);

namespace App\Component\Bulk\Parser;

use App\Component\Bulk\ParserInterface;
use App\Model\Scalar;
use Co\Channel;
use Co\WaitGroup;

use function Co\defer;
use function Co\go;
use function Co\run;

class CSVParser implements ParserInterface
{
    public function isSupportsMimeType(string $mimeType): bool
    {
        return \in_array($mimeType, [
            'application/csv',
            'text/plain',
        ], true);
    }

    public function parse(string $filename, string $mimeType): array
    {
        $fh = \fopen($filename, 'r');

        // detect csv separator
        $supportedSeparators = [',', ';', "\t"];
        $separator = null;
        $emptyColumns = null;

        foreach ($supportedSeparators as $supportedSeparator) {
            $firstLine = \fgetcsv($fh, separator: $supportedSeparator);

            /* @noinspection DisconnectedForeachInstructionInspection */
            \rewind($fh);

            if (\count($firstLine) > 1) {
                $separator = $supportedSeparator;
                $emptyColumns = \array_fill(0, \count($firstLine), true);

                break;
            }
        }

        if (null === $emptyColumns) {
            $emptyColumns = \array_fill(0, 1, true);
        }

        /** @var array<scalar[]> $rows */
        $rows = [];

        run(function () use ($fh, $separator, &$rows, &$emptyColumns): void {
            $rowsChannel = new Channel();
            $rowsWaitGroup = new WaitGroup();
            $rowNum = -1;

            go(static function () use ($rowsChannel, &$rows, &$emptyColumns): void {
                while (true) {
                    if (false === ($message = $rowsChannel->pop())) {
                        break;
                    }

                    \assert(3 === \count($message));

                    [$rowNum, $colNum, $scalar] = $message;

                    \assert(\is_int($rowNum));
                    \assert(\is_int($colNum));
                    \assert($scalar instanceof Scalar);

                    if (!isset($rows[$rowNum])) {
                        $rows[$rowNum] = [];
                    }

                    $rows[$rowNum][$colNum] = $scalar;

                    if (true === $emptyColumns[$colNum] && !$scalar->isEmpty()) {
                        $emptyColumns[$colNum] = false;
                    }
                }
            });

            while ($row = \fgetcsv($fh, separator: $separator)) {
                $rowsWaitGroup->add(1);

                go(function (array $row, int $rowNum) use ($rowsChannel, $rowsWaitGroup): void {
                    defer(static fn () => $rowsWaitGroup->done());

                    $rowWaitGroup = new WaitGroup();

                    foreach ($row as $colNum => $colValue) {
                        $rowWaitGroup->add(1);

                        go(function (string $colValue, int $rowNum, int $colNum) use ($rowWaitGroup, $rowsChannel): void {
                            defer(static fn () => $rowWaitGroup->done());

                            $colValue = \trim($colValue);
                            $scalar = new Scalar($colValue);

                            $rowsChannel->push([$rowNum, $colNum, $scalar]);
                        }, $colValue, $rowNum, $colNum);
                    }

                    $rowWaitGroup->wait();
                }, $row, ++$rowNum);
            }

            $rowsWaitGroup->wait();
            $rowsChannel->close();

            $cleaningWaitGroup = new WaitGroup();

            foreach ($emptyColumns as $colNum => $isEmpty) {
                if (!$isEmpty) {
                    continue;
                }

                $cleaningWaitGroup->add(1);

                go(function (int $colNum) use ($cleaningWaitGroup, &$rows): void {
                    defer(static fn () => $cleaningWaitGroup->done());

                    foreach ($rows as &$row) {
                        unset($row[$colNum]);
                    }
                }, $colNum);
            }

            $cleaningWaitGroup->wait();
        });

        return $rows;
    }
}
