<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $table = 'source';
    protected $primaryKey = 'id';

    public $timestamps = false;
}
