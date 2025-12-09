<?php

namespace App\Http\Controllers;

use App\Models\klasifikasi;
use App\Models\berkas;
use App\Models\hal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TabelsController extends Controller
{
    // Ambil semua data klasifikasi
    public function index()
    {
        $data = klasifikasi::all();

        return response()->json([
            'status' => true,
            'message' => 'Data klasifikasi berhasil diambil',
            'data' => $data
        ]);
    }

    // Ambil semua data berkas dengan relasi
    public function shDTables(){
        $data = berkas::with('kode', 'hal')->get();
        return response()->json([
            'status' => true,
            'message' => 'Data berkas berhasil diambil',
            'data' => $data
        ]);
    }

    /**
     * Get next number berdasarkan kode klasifikasi
     * GET /api/berkas/next-number?kode_klasifikasi=KS.01.05
     */
    public function getNextNumber(Request $request)
{
    try {
        $kodeKlasifikasi = $request->query('kode_klasifikasi');

        if (!$kodeKlasifikasi) {
            return response()->json([
                'status' => false,
                'message' => 'Kode klasifikasi tidak ditemukan'
            ], 400);
        }

        $klasifikasiExists = klasifikasi::where('Kode', $kodeKlasifikasi)->exists();

        if (!$klasifikasiExists) {
            return response()->json([
                'status' => false,
                'message' => 'Kode klasifikasi tidak valid'
            ], 404);
        }

        // Cari berkas terakhir berdasarkan kode klasifikasi
        $lastBerkas = berkas::where('kode_klasifikasi', $kodeKlasifikasi)
            ->orderBy('id', 'DESC')
            ->first();

        // Jika ada berkas, ambil hal-nya berdasarkan id_hal
        if ($lastBerkas && $lastBerkas->id_hal) {
            $lastHal = hal::find($lastBerkas->id_hal);
        } else {
            $lastHal = null;
        }

        // Tentukan nomor berikutnya
        if ($lastHal && is_numeric($lastHal->nomor)) {
            $nextNumber = intval($lastHal->nomor) + 1;
        } else {
            // Jika belum ada data, mulai dari 1
            $nextNumber = 1;
        }

        return response()->json([
            'status' => true,
            'next_number' => (string) $nextNumber,
            'kode_klasifikasi' => $kodeKlasifikasi,
            'last_berkas' => $lastBerkas ? [
                'id' => $lastBerkas->id,
                'id_hal' => $lastBerkas->id_hal,
                'no_arsip' => $lastBerkas->no_arsip
            ] : null,
            'last_hal' => $lastHal ? [
                'id' => $lastHal->id,
                'nomor' => $lastHal->nomor,
                'judul_berkas' => $lastHal->judul_berkas
            ] : null
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Gagal mendapatkan nomor berikutnya: ' . $e->getMessage()
        ], 500);
    }
}

public function store(Request $request){
    // Validasi input - disesuaikan dengan data yang dikirim
    $validated = $request->validate([
        'no_berkas' => 'required|string',
        'judul_berkas' => 'required|string',
        'items' => 'required|array|min:1',
        'items.*.no_item' => 'required|string',
        'items.*.kode' => 'required|string|exists:klasifikasi,Kode',
        'items.*.detail_klasifikasi' => 'nullable|string', // Field tambahan
        'items.*.uraian' => 'required|string',
        'items.*.tanggal' => 'required|date',
        'items.*.jumlah_angka' => 'required|numeric|min:0',
        'items.*.satuan_jumlah' => 'required|string',
        'items.*.jumlah_lengkap' => 'nullable|string', // Field tambahan
        'items.*.klasifikasi_keamanan' => 'required|string|in:biasa,rahasia,super-rahasia',
        'items.*.keterangan' => 'nullable|string'
    ]);

    try {
        DB::beginTransaction();

        $hal = hal::create([
            'nomor' => $validated['no_berkas'],
            'judul_berkas' => $validated['judul_berkas']
        ]);

        $createdItems = [];

        foreach ($validated['items'] as $item) {
            $klasifikasi = klasifikasi::where('Kode', $item['kode'])->first();

            if (!$klasifikasi) {
                throw new \Exception("Kode klasifikasi {$item['kode']} tidak ditemukan");
            }

            $berkasItem = berkas::create([
                'id_hal' => $hal->id,
                'no_arsip' => $item['no_item'],
                'kode_klasifikasi' => $item['kode'],
                'uraian_informasi' => $item['uraian'],
                'tanggal' => $item['tanggal'],
                'jumlah' => $item['jumlah_angka'],
                'satuan' => $item['satuan_jumlah'],
                'keamanan' => ucfirst($item['klasifikasi_keamanan']),
                'Keterangan' => $item['keterangan'] ?? null
            ]);

            $createdItems[] = $berkasItem;
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Data berkas berhasil disimpan',
            'data' => [
                'hal' => $hal,
                'items' => $createdItems,
                'total_items' => count($createdItems)
            ]
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => 'Validasi gagal',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => 'Gagal menyimpan data: ' . $e->getMessage()
        ], 500);
    }
}


    /**
     * Delete berkas by id
     * DELETE /api/arsip/{id}
     */
    public function destroy($id)
    {
        try {
            $berkas = berkas::findOrFail($id);
            $berkasData = $berkas->toArray();
            $berkas->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus',
                'deleted_data' => $berkasData
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
