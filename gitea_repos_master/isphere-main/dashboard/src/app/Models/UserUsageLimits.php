<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserUsageLimits extends Model
{
    static $periodTypes = [
        'month'=>'Месяц',
        'quarter'=>'Квартал',
        'year'=>'Год',
        'contract'=>'Договор',
    ];

    protected $table = 'UserUsageLimits';
    protected $primaryKey = 'Id';

    protected $guarded = ['UserId', 'Id'];

    protected $attributes = [
        'CountLimit' => 0,
        'PriceLimit' => 0,
    ];

    public $timestamps = false;

    public function getPeriodTypeLabel() {
        return self::$periodTypes[$this->PeriodType] ?? '-';
    }
}
