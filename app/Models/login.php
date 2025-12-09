<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
    protected $table = 'login';     // nama tabel
    protected $primaryKey = 'id';   // primary key
    public $timestamps = false;      // karena punya created_at & updated_at

    protected $fillable = [
        'email',
        'password'
    ];
}
