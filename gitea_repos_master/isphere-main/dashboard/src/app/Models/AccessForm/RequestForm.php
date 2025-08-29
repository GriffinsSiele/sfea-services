<?php
namespace App\Models\AccessForm;

use Illuminate\Database\Eloquent\Model;

class RequestForm extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $primaryKey = 'Code';
    protected $table = 'RequestForm';

    /*public function relations()
    {
        return $this->hasMany(RequestFormFieldRelation::class, 'FormCode', 'Code');
    }*/

    public function fields() {
        return $this->hasManyThrough(RequestFormField::class, RequestFormFieldRelation::class, 'FormCode', 'Code', 'Code', 'FieldCode');
    }
}
