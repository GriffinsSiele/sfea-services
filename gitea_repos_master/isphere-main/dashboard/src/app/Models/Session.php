<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'session';
    protected $primaryKey = 'id';

    public $timestamps = false;

    static $statusMap = [
        1 => 'Подготовка (распознавание капчи)',
        2 => 'Активная (готова к использованию)',
        3 => 'Завершена (использована полностью)',
        4 => 'Неверная капча',
        5 => 'Просрочена (закончился срок действия)',
        6 => 'Приостановлена (временно заблокирована до наступления unlocktime)',
        7 => 'Обновление (требуется повторная активация)'
    ];

}
