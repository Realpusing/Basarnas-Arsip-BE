<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class klasifikasi extends Model
{
    protected $table = 'klasifikasi';     // nama tabel
    protected $primaryKey = 'id';   // primary key
    public $timestamps = true;      // karena punya created_at & updated_at

    protected $fillable = [
        'Kode',
        'Detail_kode'
    ];
}
