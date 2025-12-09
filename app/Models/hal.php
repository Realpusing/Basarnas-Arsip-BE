<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class hal extends Model
{
    protected $table = 'no_hal_berkas';     // nama tabel
    protected $primaryKey = 'id';   // primary key
    public $timestamps = true;      // karena punya created_at & updated_at

    protected $fillable = [
        'nomor',
        'judul_berkas'
    ];
}
