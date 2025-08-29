<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessSource extends Model
{
    protected $table = 'AccessSource';
    protected $primaryKey = 'id';

    protected $attributes = [
        'allowed' => 1,
    ];

    protected $fillable = ['source_name'];

    public $timestamps = false;

    static public $names = ['2gis','announcement','avtokod','bankrot','boards','pochta','cbr','censys','commerce','dns','eaisto','egrul','elecsnet','facebook','fms','fmsdb','fns','fssp','fsspapi','fsspsite','gibdd','gisgmp','gks','googleplus','hh','infobip','instagram','ipgeobase','kad','mvd','numbuster','ok','people','phonenumber','qiwi','rz','ripe','rossvyaz','rsa','sber','sbertest','sberbank','shodan','skype','smsc','sypexgeo','terrorist','tc','viber','vk','webmoney','whatsapp','yamap','yamoney','zakupki','aeroflot','rzd','google','apple','vestnik','reestrzalogov','uralair','getcontact','papajohns','banks','alfabank','tinkoff','openbank','psbank','rosbank','unicredit','raiffeisen','sovcombank','gazprombank','mkb','rsb','avangard','qiwibank','rnko','akbars','gazenergobank','vtb','names','truecaller','emt','telegram','gosuslugi','gibdd_driver','gibdd_fines','gibdd_history','gibdd_wanted','gibdd_aiusdtp','gibdd_restricted','mailru','phones','whatsappweb','gosuslugi_passport','gosuslugi_inn','gosuslugi_phone','gosuslugi_email','hlr','numbusterapp','biglion','avito','twitter','fns_inn','vk_person','ok_person','viber_phone','whatsapp_phone','truecaller_phone','fns_invalid','rsa_policy','rsa_osagovehicle','rsa_bsostate','rsa_kbm','rsa_org','search','gibdd_diagnostic','notariat','google_name','telegramweb','callapp','icq','simpler','fotostrana','microsoft','carinfo','fssp_suspect'];

    public function access() {
        return $this->belongsTo(Access::class, 'Level', 'Level');
    }
}
