<?php

namespace App\Http\Controllers;

use App\Models\klasifikasi;
use App\Models\berkas;
use App\Models\hal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TabelsController extends Controller
{
    // =====================================================
    // KLASIFIKASI ENDPOINTS
    // =====================================================

    /**
     * Ambil semua data klasifikasi
     * GET /api/klasifikasi
     */
    public function index()
    {
        $data = klasifikasi::all();

        return response()->json([
            'status' => true,
            'message' => 'Data klasifikasi berhasil diambil',
            'data' => $data
        ]);
    }

    // =====================================================
    // BERKAS/ARSIP ENDPOINTS
    // =====================================================

    /**
     * Ambil semua data berkas dengan relasi
     * GET /api/berkas
     */
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

    /**
     * Store new berkas with items
     * POST /api/arsip/store
     */
    public function store(Request $request){
        // Validasi input - disesuaikan dengan data yang dikirim
        $validated = $request->validate([
            'no_berkas' => 'required|string',
            'judul_berkas' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.no_item' => 'required|string',
            'items.*.kode' => 'required|string|exists:klasifikasi,Kode',
            'items.*.detail_klasifikasi' => 'nullable|string',
            'items.*.uraian' => 'required|string',
            'items.*.tanggal' => 'required|date',
            'items.*.jumlah_angka' => 'required|numeric|min:0',
            'items.*.satuan_jumlah' => 'required|string',
            'items.*.jumlah_lengkap' => 'nullable|string',
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
                    'keterangan' => $item['keterangan'] ?? null
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

    // =====================================================
    // DASHBOARD ENDPOINTS - TAMBAHAN BARU
    // =====================================================

    /**
     * Get dashboard statistics
     * GET /api/dashboard/stats
     */
    public function getStats()
    {
        try {
            // Total arsip
            $total = berkas::count();

            // Count per keamanan klasifikasi (case-insensitive)
            $keamanan = [
                'biasa' => berkas::whereRaw('LOWER(keamanan) = ?', ['biasa'])->count(),
                'rahasia' => berkas::whereRaw('LOWER(keamanan) = ?', ['rahasia'])->count(),
                'super_rahasia' => berkas::whereRaw('LOWER(keamanan) IN (?, ?)', ['super rahasia', 'super-rahasia'])->count()
            ];

            return response()->json([
                'status' => true,
                'message' => 'Data statistik berhasil dimuat',
                'data' => [
                    'total' => $total,
                    'keamanan' => $keamanan
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data statistik',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get klasifikasi statistics
     * GET /api/dashboard/klasifikasi
     */
    public function getKlasifikasi()
    {
        try {
            // Count arsip per kode klasifikasi
            $klasifikasiStats = berkas::select('kode_klasifikasi', DB::raw('count(*) as jumlah'))
                ->whereNotNull('kode_klasifikasi')
                ->groupBy('kode_klasifikasi')
                ->get()
                ->map(function($item) {
                    // Get detail dari tabel klasifikasi
                    $kode = klasifikasi::where('Kode', $item->kode_klasifikasi)->first();

                    return [
                        'kode' => $item->kode_klasifikasi,
                        'detail' => $kode ? $kode->Detail_kode : 'Unknown',
                        'jumlah' => $item->jumlah
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Data klasifikasi berhasil dimuat',
                'data' => $klasifikasiStats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data klasifikasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get complete dashboard data (all in one)
     * GET /api/dashboard/summary
     */
    public function getSummary()
    {
        try {
            // Total statistics
            $total = berkas::count();

            // Count per keamanan
            $keamanan = [
                'biasa' => berkas::whereRaw('LOWER(keamanan) = ?', ['biasa'])->count(),
                'rahasia' => berkas::whereRaw('LOWER(keamanan) = ?', ['rahasia'])->count(),
                'super_rahasia' => berkas::whereRaw('LOWER(keamanan) IN (?, ?)', ['super rahasia', 'super-rahasia'])->count()
            ];

            // Klasifikasi statistics
            $klasifikasiStats = berkas::select('kode_klasifikasi', DB::raw('count(*) as jumlah'))
                ->whereNotNull('kode_klasifikasi')
                ->groupBy('kode_klasifikasi')
                ->get()
                ->map(function($item) {
                    $kode = klasifikasi::where('Kode', $item->kode_klasifikasi)->first();

                    return [
                        'kode' => $item->kode_klasifikasi,
                        'detail' => $kode ? $kode->Detail_kode : 'Unknown',
                        'jumlah' => $item->jumlah
                    ];
                });

            // Recent arsip (last 5)
            $recentArsip = berkas::with('kode', 'hal')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'no_arsip' => $item->no_arsip,
                        'uraian_informasi' => $item->uraian_informasi,
                        'tanggal' => $item->tanggal,
                        'keamanan' => $item->keamanan,
                        'kode' => $item->kode_klasifikasi,
                        'judul_berkas' => $item->hal ? $item->hal->judul_berkas : null,
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Data dashboard berhasil dimuat',
                'data' => [
                    'statistics' => [
                        'total' => $total,
                        'keamanan' => $keamanan
                    ],
                    'klasifikasi' => $klasifikasiStats,
                    'recent_arsip' => $recentArsip
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics by date range
     * GET /api/dashboard/stats/range?start_date=2024-01-01&end_date=2024-12-31
     */
    public function getStatsByRange(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Arsip created within date range
            $totalInRange = berkas::whereBetween('created_at', [$startDate, $endDate])->count();

            // Group by date
            $perDay = berkas::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as total')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Data statistik range berhasil dimuat',
                'data' => [
                    'total' => $totalInRange,
                    'per_day' => $perDay,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data statistik range',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top klasifikasi
     * GET /api/dashboard/top-klasifikasi?limit=5
     */
    public function getTopKlasifikasi(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $topKlasifikasi = berkas::select('kode_klasifikasi', DB::raw('count(*) as jumlah'))
                ->whereNotNull('kode_klasifikasi')
                ->groupBy('kode_klasifikasi')
                ->orderBy('jumlah', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($item) {
                    $kode = klasifikasi::where('Kode', $item->kode_klasifikasi)->first();

                    return [
                        'kode' => $item->kode_klasifikasi,
                        'detail' => $kode ? $kode->Detail_kode : 'Unknown',
                        'jumlah' => $item->jumlah
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Data top klasifikasi berhasil dimuat',
                'data' => $topKlasifikasi
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data top klasifikasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics by keamanan per klasifikasi
     * GET /api/dashboard/keamanan-per-klasifikasi
     */
    public function getKeamananPerKlasifikasi()
    {
        try {
            $stats = berkas::select(
                    'kode_klasifikasi',
                    'keamanan',
                    DB::raw('count(*) as jumlah')
                )
                ->whereNotNull('kode_klasifikasi')
                ->groupBy('kode_klasifikasi', 'keamanan')
                ->get()
                ->groupBy('kode_klasifikasi')
                ->map(function($items, $kodeKlasifikasi) {
                    $kode = klasifikasi::where('Kode', $kodeKlasifikasi)->first();

                    // Aggregate keamanan data
                    $keamananData = [
                        'biasa' => 0,
                        'rahasia' => 0,
                        'super_rahasia' => 0
                    ];

                    foreach ($items as $item) {
                        $keamananLower = strtolower($item->keamanan);
                        if ($keamananLower === 'biasa') {
                            $keamananData['biasa'] += $item->jumlah;
                        } elseif ($keamananLower === 'rahasia') {
                            $keamananData['rahasia'] += $item->jumlah;
                        } elseif (in_array($keamananLower, ['super rahasia', 'super-rahasia'])) {
                            $keamananData['super_rahasia'] += $item->jumlah;
                        }
                    }

                    return [
                        'kode' => $kodeKlasifikasi,
                        'detail' => $kode ? $kode->Detail_kode : 'Unknown',
                        'keamanan' => $keamananData,
                        'total' => $items->sum('jumlah')
                    ];
                })
                ->values();

            return response()->json([
                'status' => true,
                'message' => 'Data keamanan per klasifikasi berhasil dimuat',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memuat data keamanan per klasifikasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
 * Get single berkas data for edit
 * GET /api/arsip/{id}
 */
public function show($id)
{
    try {
        $berkas = berkas::with('kode', 'hal')->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil diambil',
            'data' => $berkas
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Data tidak ditemukan'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Gagal mengambil data: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Update berkas by id
 * PUT /api/arsip/{id}
 */
public function update(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'no_arsip' => 'required|string',
            'kode_klasifikasi' => 'required|string|exists:klasifikasi,Kode',
            'uraian_informasi' => 'required|string',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:0',
            'satuan' => 'required|string',
            'keamanan' => 'required|string|in:biasa,rahasia,super-rahasia,Biasa,Rahasia,Super-rahasia',
            'keterangan' => 'nullable|string'
        ]);

        DB::beginTransaction();

        $berkas = berkas::findOrFail($id);

        $berkas->update([
            'no_arsip' => $validated['no_arsip'],
            'kode_klasifikasi' => $validated['kode_klasifikasi'],
            'uraian_informasi' => $validated['uraian_informasi'],
            'tanggal' => $validated['tanggal'],
            'jumlah' => $validated['jumlah'],
            'satuan' => $validated['satuan'],
            'keamanan' => ucfirst($validated['keamanan']),
            'keterangan' => $validated['keterangan'] ?? 'Tekstual'
        ]);

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil diupdate',
            'data' => $berkas->fresh()
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Validasi gagal',
            'errors' => $e->errors()
        ], 422);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Data tidak ditemukan'
        ], 404);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Gagal mengupdate data: ' . $e->getMessage()
        ], 500);
    }
}
}
