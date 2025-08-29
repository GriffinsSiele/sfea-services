<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $guarded = ['id'];
    static $typesList = ['string', 'map', 'text', 'url', 'image', 'float', 'email'];


    public $timestamps = false;

    protected $table = 'Field';
    protected $primaryKey = 'id';
}
