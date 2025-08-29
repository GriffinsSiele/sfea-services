<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Validator;

class Phone extends Model
{
    static $parentTypes = [
        'client',
        'user'
    ];

    protected $attributes = [
        'InnerCode' => '',
        'Notice' => '',
    ];

    protected $table = 'Phone';
    protected $primaryKey = 'Id';

    protected $guarded = ['Id'];

    public $timestamps = false;

    public function compileToString()
    {
        return implode(';', [$this->Number, $this->InnerCode, $this->Notice]);
    }

    static function compileListToStringArray($phonesObjects) {
        $strings = [];
        foreach ($phonesObjects as $po)
            $strings[] = $po->compileToString();

        return $strings;
    }

    static function fillAndSaveList($phones, $parentId, $parentType, &$phonesObjects = NULL) {

        $phoneRules = ['Number' => 'regex:/^7[0-9]{10}$/|Nullable'];
        $phoneRules = [];

        $ids = [0];

        foreach ($phones as $phone) {
            if(empty($phone['Number']))
                continue;

            $phone = array_filter($phone, 'strlen');

            $validator = Validator::make($phone, $phoneRules, ['Number.regex' => 'Формат номера телефона должен быть 79999999999',]);

            if ($validator->fails())
                return $validator;

            $phoneObj = null;

            if(isset($phone['Id'])) {
                $phoneObj = Phone::where('Id', $phone['Id'])
                    ->where('ParentId', $parentId)
                    ->where('ParentType', $parentType)
                    ->first();
            }

            if(!$phoneObj) {
                $phoneObj = new Phone();
                $phoneObj->ParentId = $parentId;
                $phoneObj->ParentType = $parentType;
            }

            $phoneObj->fill($phone);

            $phoneObj->save();

            $ids[] = $phoneObj->Id;

            if(!is_null($phonesObjects))
                $phonesObjects[] = $phoneObj;
        }

        Phone::whereNotIn('Id', $ids)
            ->where('ParentId', $parentId)
            ->where('ParentType', $parentType)
            ->delete();

        return true;
    }
}
