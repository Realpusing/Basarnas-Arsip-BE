<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TabelsController;
use App\Http\Controllers\KlasifikasiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// =====================================================
// HELLO WORLD / TESTING
// =====================================================
Route::get('/hello', function () {
    return response()->json(['message' => 'Hello API']);
});
Route::prefix('klasifikasi')->group(function () {
    Route::get('/', [KlasifikasiController::class, 'index']); // Ambil semua data
    Route::get('/active', [KlasifikasiController::class, 'getActive']); // Ambil data aktif saja
    Route::post('/', [KlasifikasiController::class, 'store']); // Tambah data
    Route::put('/{id}', [KlasifikasiController::class, 'update']); // Edit data
    Route::patch('/{id}/toggle-status', [KlasifikasiController::class, 'toggleStatus']); // Ubah status aktif/nonaktif
});

// =====================================================
// HEALTH CHECK
// =====================================================
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => now()->toDateTimeString(),
        'endpoints' => [
            'dashboard' => [
                'GET /api/dashboard/stats',
                'GET /api/dashboard/klasifikasi',
                'GET /api/dashboard/summary',
                'GET /api/dashboard/stats/range',
                'GET /api/dashboard/top-klasifikasi',
                'GET /api/dashboard/keamanan-per-klasifikasi'
            ],
            'klasifikasi' => [
                'GET /api/klasifikasi'
            ],
            'arsip' => [
                'GET /api/berkas',
                'GET /api/berkas/next-number',
                'POST /api/arsip/store',
                'GET /api/arsip/{id}',
                'PUT /api/arsip/{id}',
                'DELETE /api/arsip/{id}'
            ]
        ]
    ]);
});

// =====================================================
// DASHBOARD ROUTES
// =====================================================
Route::prefix('dashboard')->group(function () {
    Route::get('/stats', [TabelsController::class, 'getStats']);
    Route::get('/klasifikasi', [TabelsController::class, 'getKlasifikasi']);
    Route::get('/summary', [TabelsController::class, 'getSummary']);
    Route::get('/stats/range', [TabelsController::class, 'getStatsByRange']);
    Route::get('/top-klasifikasi', [TabelsController::class, 'getTopKlasifikasi']);
    Route::get('/keamanan-per-klasifikasi', [TabelsController::class, 'getKeamananPerKlasifikasi']);
});


// BERKAS/ARSIP ROUTES
// =====================================================
Route::prefix('berkas')->group(function () {
    Route::get('/', [TabelsController::class, 'shDTables']);
    Route::get('/next-number', [TabelsController::class, 'getNextNumber']);
});

Route::prefix('arsip')->group(function () {
    Route::post('/store', [TabelsController::class, 'store']);
    Route::get('/{id}', [TabelsController::class, 'show']);
    Route::put('/{id}', [TabelsController::class, 'update']);
    Route::delete('/{id}', [TabelsController::class, 'destroy']);
});
