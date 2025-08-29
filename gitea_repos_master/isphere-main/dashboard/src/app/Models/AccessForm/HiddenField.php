<?php
namespace App\Models\AccessForm;

use Illuminate\Database\Eloquent\Model;

class HiddenField extends Model
{
    protected $table = 'HiddenAccessField';
    protected $primaryKey = 'Id';

    public $timestamps = false;

    public function relation() {
        return $this->hasOne(RequestFormFieldRelation::class, 'Id', 'RFFRelationId');
    }
}
