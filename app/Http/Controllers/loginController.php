<?php

namespace App\Http\Controllers;

use App\Models\login;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    // ambil semua data login
    public function index()
    {
        $data = login::all();

        return response()->json([
            'status' => true,
            'message' => 'Data login berhasil diambil',
            'data' => $data
        ]);
    }

    // ambil satu orang (by id)
    public function show($id)
    {
        $data = Login::find($id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }
}
