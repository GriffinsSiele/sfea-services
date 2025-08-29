<?php

declare(strict_types=1);

namespace App\Component\Bulk\Parser;

use App\Component\Bulk\ParserInterface;
use App\Model\Scalar as AppScalar;
use avadim\FastExcelReader\Excel;

class XLSXParser implements ParserInterface
{
    public function isSupportsMimeType(string $mimeType): bool
    {
        return \in_array($mimeType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ], true);
    }

    public function parse(string $filename, string $mimeType): array
    {
        $excel = Excel::open($filename)
            ->setDateFormat('Y-m-d');

        /** @var array<AppScalar[]> $rows */
        $rows = [];

        if (null === ($sheet = $excel->sheet())) {
            return [];
        }

        foreach ($sheet->nextRow(indexStyle: Excel::KEYS_FIRST_ROW) as $rowData) {
            $row = [];

            foreach ($rowData as $v) {
                $v = \trim((string) $v);
                $row[] = new AppScalar(!empty($v) ? $v : null);
            }

            $rows[] = $row;
        }

        \gc_collect_cycles();

        return $rows;
    }
}
