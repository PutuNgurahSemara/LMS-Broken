<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use Illuminate\Support\Facades\Route;

// Bug 7 FIX: Tambahkan throttle pada endpoint login untuk mencegah brute-force attack.
// Tanpa rate limiting, attacker bisa mencoba ribuan kombinasi password tanpa hambatan apapun.
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    // Bug 3 FIX: courses.show dipindahkan ke dalam grup middleware auth:sanctum.
    // Sebelumnya berada di luar group — endpoint bisa diakses tanpa token Sanctum apapun,
    // melewati seluruh lapisan autentikasi API.
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
});
