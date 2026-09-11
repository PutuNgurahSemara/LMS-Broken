<?php

use App\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');

// MASALAH 1 create dibawah /courses/{id}

// SEBELUM (SALAH):
// Route::get('/courses/{id}', [CourseController::class, 'show'])->name('courses.show');
// Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');

// SESUDAH (BENAR):
Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');

Route::get('/courses/{id}', [CourseController::class, 'show'])->name('courses.show');

// MASALAH 2 Delete action menggunakan GET padahal seharusnya DELETE (atau minimal POST)

// Sebelum (salah)
// Route::get('/courses/{id}/delete', [CourseController::class, 'destroy'])->name('courses.destroy.broken');

// Sesudah (benar)
Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');



