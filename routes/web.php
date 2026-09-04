<?php

use App\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SEBELUMNYA SALAH:
// Route::get('/courses/{id}', [CourseController::class, 'show'])->name('courses.show');
// Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
// Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
// -> /courses/{id} ditulis SEBELUM /courses/create, jadi kata "create" ikut
//    tertangkap sebagai nilai {id} dan memanggil show('create'), bukan create().
// PERBAIKAN: /courses/create dipindah ke atas /courses/{id}.
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
Route::get('/courses/{id}', [CourseController::class, 'show'])->name('courses.show');

// SEBELUMNYA SALAH:
// Route::get('/courses/{id}/delete', [CourseController::class, 'destroy'])->name('courses.destroy.broken');
// -> Menghapus data lewat method GET berbahaya, bisa terpicu tanpa sengaja
//    oleh crawler, preview link, atau prefetch browser.
// PERBAIKAN: ganti jadi method DELETE, dipanggil lewat form + @method('DELETE').
Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');
