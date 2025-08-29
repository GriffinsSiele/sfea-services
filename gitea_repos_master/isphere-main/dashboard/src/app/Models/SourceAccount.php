<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceAccount extends Model
{
    static $statusMap = [
        0 => 'Отключен',
        1 => 'Активен',
        -1=> 'В резерве'
    ];

    protected $table = 'sourceaccess';
    protected $primaryKey = 'sourceaccessid';

    protected $fillable = ['sourceid','login', 'password','note','status','userid', 'clientid','unlocktime'];

    protected $attributes = [
        'clientid'=> 0,
        'note' => '-'
    ];

    public $timestamps = false;

    public function client() {
        return $this->hasOne(Client::class, 'id', 'clientid');
    }

    public function source() {
        return $this->hasOne(Source::class, 'id', 'sourceid');
    }

    public function lastSession() {

        switch ($this->source->code) {
            case 'getcontact_web' :
            case 'getcontact_app' :
                return $this->hasOne(SessionGetcontact::class, 'sourceaccessid', 'sourceaccessid')->orderBy('id', 'desc')->limit(1);
            case 'gosuslugi' :
                return $this->hasOne(SessionGosuslugi::class, 'sourceaccessid', 'sourceaccessid')->orderBy('id', 'desc')->limit(1);
            default:
                return $this->hasOne(Session::class, 'sourceaccessid', 'sourceaccessid')->orderBy('id', 'desc')->limit(1);
        }

    }
}
