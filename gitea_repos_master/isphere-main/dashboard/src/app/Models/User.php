<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Panoscape\History\HasOperations;
use Panoscape\History\HasHistories;

class User extends Authenticatable
{
    use HasOperations;
    //use HasHistories;
    use ObjectHistory;

    public function getModelLabel()
    {
        return 'Пользователь';
    }

    static $fieldsLabels = [
        'phones'=>'Телефоны',
        'Login'=>'Логин',
        'Name'=>'Имя',
        'Locked'=>'Заблокирован',
        'Deleted'=>'Удалён',
        'Email'=>'Почта',
        'OrgName'=>'Организация',
        'AllowedIP'=>'Разрешённый IP',
        'AccessLevel'=>'Тип доступа',
        'AccessArea'=>'Видимость пользователей',
        'ReportsArea'=>'Видимость запросов для отчетов',
        'ResultsArea'=>'Видимость запросов для просмотра результатов',
        'StartTime'=>'Дата/время начала доступа',
        'EndTime'=>'Дата/время окончания доступа',
        'ClientId'=>'Клиент',
        'MasterUserId'=>'Основной пользователь',
        'ParallelQueriesLimit'=>'Лимит параллельных запросов',
    ];

    static $lockedMap = [
        0=>'Нет',
        1=>'Да',
    ];

    static $accessAreaMap = [
        '0' => 'Своя учётная запись',
        '1' => 'Свои пользователи (2 уровня)',
        '2' => 'Пользователи клиента',
        '3' => 'Пользователи своих клиентов',
        '4' => 'Все',
    ];

    static $resultsAreaMap = [
        '0' => 'Своя учётная запись',
        '1' => 'Свои пользователи (2 уровня)',
        '2' => 'Пользователи клиента',
        '3' => 'Пользователи своих клиентов',
        '4' => 'Все',
    ];

    static $reportsAreaMap = [
        '0' => 'Своя учётная запись',
        '1' => 'Свои пользователи (2 уровня)',
        '2' => 'Пользователи клиента',
        '3' => 'Пользователи своих клиентов',
        '4' => 'Все',
    ];

    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'SystemUsers';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    //public $id = 0;

    protected $guarded = ['MasterUserId'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'Name',
        'Email',
        'Login',
        'OrgName',
        'Locked',
        'Phone',
        //'AllowedIP',
        //'StartTime'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'Password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'Locked' => 'boolean',
        'Deleted' => 'datetime',
    ];

    public function getAuthIdentifierName() {
        //$this->id = $this->Id; // Костыль для Panoscape\History
        return 'Id';
    }

    public function getAuthIdentifier() {
        //$this->id = $this->Id; // Костыль для Panoscape\History
        return $this->Id;
    }

    public function __get($key) {

        if($key == 'id') // Костыль для Panoscape\History
            return $this->Id;

        return parent::__get($key);
    }

    public function getAuthPassword()
    {
        return $this->Password;
    }

    public function phones() {
        return $this->hasMany(Phone::class, 'ParentId', 'Id')
            ->where('ParentType', 'user')
            ->orderBy('Id', 'asc');
    }

    public function access() {
        return $this->hasOne(Access::class, 'Level', 'AccessLevel');
    }

    public function client() {
        return $this->hasOne(Client::class, 'id', 'ClientId');
    }

    public function message() {
        return $this->hasOne(Message::class, 'id', 'MessageId');
    }

    public static function generatePassword() {
        return file_get_contents(env('USER_NEW_PASSWORD_URL'));
    }

    public static function checkLogin($login) {
        return User::query()->where('Login', $login)->first() ? 1 : 0;
    }

    public static function mapExcel($usersData, $loginPrefix = null) {

        $loginOffsets= [];
        $users = [];

        foreach ($usersData[0] as $userData) {

            $userData['login'] = trim($userData['login']);
            $userData['login'] = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $userData['login']);

            $user = new User(['Name'=>$userData['fio'] ?? '']);

            if(!empty($userData['pocta'])) {
                $user->Email = $userData['pocta'];

                preg_match('/([^@]+)@/', $userData['pocta'], $match);

                $baseLogin = $loginPrefix ? $loginPrefix.$match[1] : $match[1];
            }
            elseif(!empty($userData['login']))
                $baseLogin = $userData['login'];
            else
                $baseLogin = $loginPrefix;

            if(empty($userData['fio']) && !strlen($baseLogin))
                continue;

            if(!isset($loginOffsets[$baseLogin]))
                $loginOffsets[$baseLogin] = 0;

            do{
                $resLogin = $baseLogin.($loginOffsets[$baseLogin] ? $loginOffsets[$baseLogin] : '');
                $loginOffsets[$baseLogin]++;
            }while (User::checkLogin($resLogin));

            $user->Login = $resLogin;

            $users[] = $user;
        }

        return $users;
    }

    public static function getUsersQuery($user) {

        //$users = User::orderBy('Id', 'desc');
        $users = User::query();

        switch ($user->AccessArea) {
            case 0 :
                $users
                    ->where('Id', $user->Id);
                break;

            case 1 :
                $subUsersIds = User::where('MasterUserId', $user->Id)
                    ->pluck('Id')
                    ->toArray(); // second level

                $subUsersIds[] = $user->Id;

                $users
                    ->where('Id', $user->Id)
                    ->orWhereIn('MasterUserId', $subUsersIds);
                    //->orWhere('ClientId', $user->ClientId)

                break;

            case 2 :
                $users->where('ClientId', $user->ClientId);

                break;

            case 3 :
                $clientIds = Client::where('MasterUserId', $user->Id)
                    ->pluck('Id')
                    ->toArray();

                $clientIds[] = $user->ClientId;

                $users->whereIn('ClientId', $clientIds);

                break;
        }

        return $users;
    }
}
