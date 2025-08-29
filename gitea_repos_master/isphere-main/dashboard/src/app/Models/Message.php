<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected $table = 'Message';
    protected $primaryKey = 'id';
}
