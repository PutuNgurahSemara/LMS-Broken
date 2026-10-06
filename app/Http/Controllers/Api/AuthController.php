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
        //no 7 mengubah *if (! $user) {return response()->json( 'message' => 'Email tidak terdaftar dalam sistem.',], 401); }* menjadi *if (! $user || ! Hash::check($request->password, $user->password)) {return response()->json( 'message' => 'Email atau password yang Anda masukkan salah.',], 401); }* agar response lebih aman dan tidak mengungkapkan apakah email terdaftar atau tidak. Hal ini dilakukan untuk mencegah potensi serangan enumerasi akun di mana penyerang dapat mengetahui apakah email tertentu terdaftar di sistem.
        if (! $user || ! Hash::check($request->password, $user->password)) {
        RateLimiter::hit($throttleKey);

        return response()->json([
        'message' => 'Email atau password yang Anda masukkan salah.',
        ], 401);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Password yang Anda masukkan salah.',
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
