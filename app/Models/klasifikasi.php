<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class klasifikasi extends Model
{
    protected $table = 'klasifikasi';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'Kode',
        'Detail_kode',
        'is_active' // Tambahkan ini
    ];
}
