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

class UsersExport implements FromQuery, ShouldAutoSize, WithMapping, WithHeadings, WithStyles
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
            'Логин',
            'Имя',
            'Заблокирован',
            'Удалён',
            'Почта',
            'Телефон',
            'Организация',
            'Разрешённый IP',
            'Видимость пользователей',
            'Тип доступа',
            'Клиент',
            'Объявление',
        ];
    }

    public function map($row): array
    {
        return [
            $row->Login,
            $row->Name,
            \App\Models\User::$lockedMap[$row->Locked] ?? '-',
            $row->Deleted ? 'Да' : 'Нет',
            $row->Email,
            array_reduce( $row->phones->toArray(), function ($phoneLine, $phone) {
                $phoneLine .= trim($phone['Number'] . ' ' . $phone['InnerCode'] . ' ' . $phone['Notice']).'; ';
                return $phoneLine;
            }, ''),
            $row->OrgName,
            $row->AllowedIP,
            \App\Models\User::$accessAreaMap[$row->AccessArea] ?? '-',
            $row->access ? $row->access->Name : '-',
            $row->client ? $row->client->OfficialName : '-',
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
