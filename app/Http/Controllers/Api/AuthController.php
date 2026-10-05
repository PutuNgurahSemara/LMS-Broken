<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Bug 8 FIX: Gabungkan pengecekan email dan password menjadi satu kondisi
        // dengan pesan error yang identik untuk kedua kasus.
        // Sebelumnya: pesan berbeda → attacker bisa menguji apakah email terdaftar
        // (User Enumeration / Email Enumeration attack). Informasi ini menjadi modal
        // awal untuk serangan lanjutan seperti credential stuffing atau phishing.
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau kata sandi yang Anda masukkan tidak valid.',
            ], 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout'], 200);
    }
}
