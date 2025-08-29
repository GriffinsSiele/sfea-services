<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    protected $table = 'Access';
    protected $primaryKey = 'Level';

    protected $fillable = ['Name','history','bulk',
        'check','checkphone','checkemail','checkurl','checkorg','checkphone_by','checkphone_kz','checkphone_uz','checkphone_uz','checkphone_bg','checkphone_ro','checkphone_pl','checkphone_pt','checktext','checkskype','checkauto','checkip','checkcard',
        'stats','users','reports', 'sources'];

    public $timestamps = false;

    public function accessSources() {
        return $this->hasMany(AccessSource::class, 'Level', 'Level');
    }

    public function fromHiddenFields() {
        return $this->hasMany(AccessForm\HiddenField::class, 'AccessId', 'Level');
    }

    public function fromHiddenFieldsMap() {
        $map = [];
        foreach ($this->fromHiddenFields as $field)
            $map[$field->relation->field->Code][$field->relation->object->Code] = 1;

        return $map;
    }
}
