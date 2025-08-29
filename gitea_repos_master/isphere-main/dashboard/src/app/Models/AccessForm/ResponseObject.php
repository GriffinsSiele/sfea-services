<?php
namespace App\Models\AccessForm;

use Illuminate\Database\Eloquent\Model;

class ResponseObject extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'ResponseObject';
    protected $primaryKey = 'Code';
}
