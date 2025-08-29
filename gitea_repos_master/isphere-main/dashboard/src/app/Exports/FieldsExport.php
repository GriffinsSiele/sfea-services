<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FieldsExport implements FromQuery, ShouldAutoSize, WithMapping, WithHeadings, WithStyles
{
    use Exportable;

    protected $query;

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }

    public function headings(): array
    {
        return [
            'Источник',
            'Проверка',
            'Код',
            'Тип',
            'Название',
            'Описание',
        ];
    }

    public function map($row): array
    {
        return [
            $row->source_name,
            $row->checktype,
            $row->name,
            $row->type,
            $row->title,
            $row->description,
        ];
    }

    public function columnFormats(): array
    {
        return [
            //'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            //'C' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
        ];
    }

    function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }
}
