<?php

namespace App\Http\Controllers;

use App\Models\klasifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KlasifikasiController extends Controller
{
    // 1. Tampilkan semua klasifikasi (Untuk halaman manajemen tabel di Frontend)
    public function index()
    {
        // Mengambil semua data dan diurutkan berdasarkan Kode
        $data = klasifikasi::orderBy('Kode', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    // 2. Tampilkan HANYA yang aktif (Gunakan ini nanti untuk Dropdown saat tambah Arsip)
    public function getActive()
    {
        $data = klasifikasi::where('is_active', true)->orderBy('Kode', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    // 3. Tambah klasifikasi baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Kode' => 'required|unique:klasifikasi,Kode',
            'Detail_kode' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $klasifikasi = klasifikasi::create([
            'Kode' => $request->Kode,
            'Detail_kode' => $request->Detail_kode,
            'is_active' => true // Default selalu aktif saat baru dibuat
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Klasifikasi berhasil ditambahkan',
            'data' => $klasifikasi
        ]);
    }

    // 4. Edit klasifikasi
    public function update(Request $request, $id)
    {
        $klasifikasi = klasifikasi::find($id);

        if (!$klasifikasi) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        // Validasi, pastikan Kode unik tapi abaikan ID yang sedang diedit
        $validator = Validator::make($request->all(), [
            'Kode' => 'required|unique:klasifikasi,Kode,'.$id,
            'Detail_kode' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $klasifikasi->update([
            'Kode' => $request->Kode,
            'Detail_kode' => $request->Detail_kode,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Klasifikasi berhasil diperbarui',
            'data' => $klasifikasi
        ]);
    }

    // 5. Aktifkan / Nonaktifkan (Toggle Status)
    public function toggleStatus($id)
    {
        $klasifikasi = klasifikasi::find($id);

        if (!$klasifikasi) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        // Balikkan statusnya (true jadi false, false jadi true)
        $klasifikasi->is_active = !$klasifikasi->is_active;
        $klasifikasi->save();

        $statusLabel = $klasifikasi->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'status' => 'success',
            'message' => "Klasifikasi berhasil $statusLabel",
            'is_active' => $klasifikasi->is_active
        ]);
    }
}
