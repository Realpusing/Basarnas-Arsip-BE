<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class berkas extends Model
{
    protected $table = 'berkas';     // nama tabel
    protected $primaryKey = 'id';   // primary key
    public $timestamps = true;      // karena punya created_at & updated_at

    protected $fillable = [
        'id_hal',
        'no_arsip',
        'kode_klasifikasi',
        'uraian_informasi',
        'tanggal',
        'jumlah',
        'keamanan',
        'Keterangan',
        'satuan'
    ];
    public function hal(){
        return $this->belongsTo(hal::class, 'id_hal', 'id');
    }
    public function kode(){
        return $this->belongsTo(klasifikasi::class, 'kode_klasifikasi', 'Kode');
    }

}
