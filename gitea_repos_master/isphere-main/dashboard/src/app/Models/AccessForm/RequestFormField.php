<?php
namespace App\Models\AccessForm;

use Illuminate\Database\Eloquent\Model;

class RequestFormField extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'RequestFormField';
    protected $primaryKey = 'Code';

    public function objects() {
        return $this->hasManyThrough(ResponseObject::class, RequestFormFieldRelation::class, 'FieldCode', 'Code', 'Code', 'ObjectCode');
    }
}
