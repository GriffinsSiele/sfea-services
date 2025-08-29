<?php

declare(strict_types=1);

namespace App\Component\Bulk\Printer;

use App\Component\Bulk\Model\RenderingData;
use App\Component\Bulk\PrinterInterface;
use avadim\FastExcelWriter\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class XLSXPrinter implements PrinterInterface
{
    public function printRenderingData(RenderingData $renderingData): UploadedFile
    {
        $excel = Excel::create(\array_keys($renderingData->getHeaders()));

        foreach ($renderingData->getRows() as $sourceCode => $sourceRows) {
            $sheet = $excel->getSheet($sourceCode);

            \assert(null !== $sheet);

            $row = [];

            foreach ($renderingData->getHeaders()[$sourceCode] as $header) {
                $row[$header] = '@string';

                $sheet->setColWidthAuto(Coordinate::stringFromColumnIndex(\count($row)));
            }

            foreach ($sourceRows as $sourceRow) {
                $sheet->writeRow(\array_values($sourceRow));
            }
        }

        $filename = \tempnam(\sys_get_temp_dir(), 'excel').'.xlsx';

        $excel->save($filename);

        return new UploadedFile($filename, 'excel.xlsx');
    }
}
