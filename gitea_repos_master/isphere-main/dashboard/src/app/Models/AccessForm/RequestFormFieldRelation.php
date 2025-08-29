<?php
namespace App\Models\AccessForm;

use Illuminate\Database\Eloquent\Model;

class RequestFormFieldRelation extends Model
{
    protected $table = 'RequestFormFieldRelation';
    protected $primaryKey = 'Id';

    public function field() {
        return $this->hasOne(RequestFormField::class, 'Code', 'FieldCode');
    }

    public function object() {
        return $this->hasOne(ResponseObject::class, 'Code', 'ObjectCode');
    }

    public function form() {
        return $this->hasOne(RequestForm::class, 'Code', 'FormCode');
    }
}
