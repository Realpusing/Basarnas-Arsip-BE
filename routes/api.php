<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\TabelsController;



Route::get('/hello', function () {
    return response()->json(['message' => 'Hello API']);
});

Route::get('/klasifikasi', [TabelsController::class, 'index']);
Route::get('/berkas', [TabelsController::class, 'shDTables']);
Route::get('/berkas/next-number', [TabelsController::class, 'getNextNumber']);
Route::post('/arsip/store', [TabelsController::class, 'store']);
Route::delete('/arsip/{id}', [TabelsController::class, 'destroy']);

Route::get('/login', [loginController::class, 'index']);
Route::get('/login/{id}', [loginController::class, 'show']);

