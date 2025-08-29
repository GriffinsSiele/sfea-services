<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientUsageLimits extends Model
{
    static $periodTypes = [
        'month'=>'Месяц',
        'quarter'=>'Квартал',
        'year'=>'Год',
        'contract'=>'Договор',
    ];

    protected $table = 'ClientUsageLimits';
    protected $primaryKey = 'Id';

    protected $guarded = ['ClientId', 'Id'];

    protected $attributes = [
        'CountLimit' => 0,
        'PriceLimit' => 0,
    ];

    public $timestamps = false;

    public function getPeriodTypeLabel() {
        return self::$periodTypes[$this->PeriodType] ?? '-';
    }
}
