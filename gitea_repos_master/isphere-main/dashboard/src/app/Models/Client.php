<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Panoscape\History\HasHistories;

class Client extends Model
{
    use ObjectHistory;

    static $fieldsLabels = [
        'phones'=>'Телефоны',
        'Name'=>'Название',
        'OfficialName'=>'Юридическое название',
        'Status'=>'Статус',
        'Code'=>'Код',
        'TariffId'=>'Тариф',
        'Email'=>'Почта',
        'Address'=>'Адрес',
        'INN'=>'ИНН',
        'OGRN'=>'ОГРН',
        'KPP'=>'КПП',
        'Bank'=>'Банк',
        'BIK'=>'БИК',
        'BankAccount'=>'Р/С',
        'ContactName'=>'Контактное лицо по договору',
        'ContractNum'=>'Номер договора',
        'TariffStartDate'=>'Дата начала тарификации',
        'ContractStartDate'=>'Дата заключения договора',
        'ContractStopDate'=>'Дата расторжения договора',
        'StartTime'=>'Дата/время начала доступа',
        'EndTime'=>'Дата/время окончания доступа',
        'MessageId'=>'Id объявления',
    ];

    public function getModelLabel()
    {
        return 'Клиент';
    }

    static $statusMap = [
        //''=>'Не задано',
        -1=>'Расторгнут',
        0=>'Приостановлен',
        1=>'Действующий',
        2=>'Новый (заявка)',
        3=>'Отказался',
        4=>'Тестирование',
    ];

    protected $table = 'Client';
    protected $primaryKey = 'id';

    protected $guarded = ['id', 'EndTime', 'StartTime', 'MessageId', 'MasterUserId'];

    public $timestamps = false;

    public function tariff() {
        return $this->hasOne(Tariff::class, 'id', 'TariffId');
    }

    public function message() {
        return $this->hasOne(Message::class, 'id', 'MessageId');
    }

    public function phones() {
        return $this->hasMany(Phone::class, 'ParentId', 'id')
            ->where('ParentType', 'client')
            ->orderBy('Id', 'asc');
    }
    public function usageLimits() {
        return $this->hasMany(ClientUsageLimits::class, 'ClientId', 'id');
    }
}
