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
use PhpOffice\PhpSpreadsheet\Shared\Date;


class ClientsExport implements FromQuery, ShouldAutoSize, WithMapping, WithHeadings, WithStyles
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
            'Название',
            'Юридическое название',
            'Статус',
            'Код',
            'Тариф',
            'Телефон',
            'Почта',
            'Адрес',
            'ИНН',
            'ОГРН',
            'КПП',
            'БИК',
            'Банк',
            'Р/С',
            'Контактное лицо по договору',
            'Номер договора',
            'Дата заключения договора',
            'Дата расторжения договора',
            'Объявление',
        ];
    }

    public function map($row): array
    {
        return [
            $row->Name,
            $row->OfficialName,
            \App\Models\Client::$statusMap[$row->Status] ?? '-',
            $row->Code,
            $row->tariff ? $row->tariff->Name : '-',
            array_reduce( $row->phones->toArray(), function ($phoneLine, $phone) {
                $phoneLine .= trim($phone['Number'] . ' ' . $phone['InnerCode'] . ' ' . $phone['Notice']).'; ';
                return $phoneLine;
            }, ''),
            $row->Email,
            $row->Address,
            $row->INN,
            $row->OGRN,
            $row->KPP,
            $row->BIK,
            $row->Bank,
            $row->BankAccount,
            $row->ContactName,
            $row->ContactNum,
            $row->ContractStartDate,
            $row->ContractStopDate,
            //Date::dateTimeToExcel($row->ContractStartDate),
            //Date::dateTimeToExcel($row->ContractStopDate),
            $row->message ? $row->message->Text : '-',
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
