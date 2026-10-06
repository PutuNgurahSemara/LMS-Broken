<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use Illuminate\Support\Facades\Route;
//no 6 menambahkan *Route::middleware('throttle:5,1')->group(function () { Route::post('/login', [AuthController::class, 'login']); });* agar endpoint login dibatasi hanya 5 request per menit untuk mencegah brute force attack. Jika user melakukan lebih dari 5 request dalam 1 menit, maka akan menerima response *429 Too Many Requests*.
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// no 2 memindahkan *Route::get('/courses/{course}', [CourseController::class, 'show']);* ke dalam *Route::middleware('auth:sanctum')* agar hanya user yang sudah login yang bisa mengakses endpoint tersebut. Hal ini dilakukan untuk menjaga keamanan data course dan mencegah akses tidak sah.
Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
});
