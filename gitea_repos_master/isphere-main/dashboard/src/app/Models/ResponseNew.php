<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponseNew extends Model
{
    protected $table = 'ResponseNew';
    protected $primaryKey = 'id';

    public $timestamps = false;

    public function user() {
        return $this->hasOne(User::class, 'Id', 'user_id');
    }

    public function client() {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
}
