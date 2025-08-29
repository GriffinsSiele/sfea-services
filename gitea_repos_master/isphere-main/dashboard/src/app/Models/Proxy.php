<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    protected $table = 'proxy';
    protected $primaryKey = 'id';

    public $timestamps = false;

    static $countriesList = ['ru','tr','by','kz','us','it','be','fi','md','fr','il','de',];

    protected $guarded = ['id'];
}
