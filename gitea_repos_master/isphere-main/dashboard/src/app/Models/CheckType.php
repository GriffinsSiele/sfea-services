<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Panoscape\History\HasHistories;

class CheckType extends Model
{
    use ObjectHistory;

    static $statusMap = [
        -1=>'Выключен',
        0=>'Неисправен',
        1=>'Работает',
    ];

    static $fieldsLabels = [
        'code'=>'Код',
        'title'=>'Название',
    ];


    protected $table = 'CheckType';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function getModelLabel()
    {
        return 'Проверка';
    }
}
