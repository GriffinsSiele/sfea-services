<?php

namespace App\Imports;

use App\Models\User;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;

use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

class UsersImport implements ToModel, WithHeadingRow, ToCollection//, WithEvents
{
    use \Maatwebsite\Excel\Concerns\Importable;


    public function collection(\Illuminate\Support\Collection $rows)
    {
        return $rows->transform(function($row) {

            var_dump($row);
            exit();

            return [
                'date' => $row[0],
                'site' => $row[1],
                'impressions' => $row[2],
                'revenue' => $row[3]
            ];
        });

        return $rows;
    }


    public function model(array $row)
    {
        return new User([
            'Name' => $row['fio']
        ]);
    }

    /*
    public function mapping(): array
    {
        $this->columnsMap = [
            'Login'  => 'B1',
            'Name' => 'B2',
            'Email' => 'B2',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $header = $event->sheet->first();

                var_dump($header);
                exit();


            }
        ];
    }
    */
}
