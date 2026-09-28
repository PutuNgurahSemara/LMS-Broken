<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Bug 1 FIX: Route courses.show dipindahkan ke dalam grup middleware 'auth'
// Sebelumnya berada di luar grup sehingga bisa diakses tanpa login

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Bug 1 FIX: courses.show sekarang berada di dalam grup auth
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

    // Bug 6 FIX: Nama route courses.index yang bentrok antar peran diperbaiki
    // Sebelumnya: dua route berbeda sama-sama bernama 'courses.index'
    Route::get('/lecturer/courses', [CourseController::class, 'index'])->middleware('role:dosen')->name('courses.lecturer.index');
    Route::get('/student/courses', [CourseController::class, 'index'])->middleware('role:mahasiswa')->name('courses.student.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    Route::post('/courses/{course}/materials', [MaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');
    // Bug 7 FIX: Route destruktif diganti dari Route::get ke Route::delete
    // Sebelumnya: Route::get('/materials/{material}/delete', ...) — operasi hapus tidak boleh pakai GET
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');

    Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    // Bug 4 FIX: Tambah scopeBindings() agar {assignment} divalidasi benar-benar milik {course}
    // Tanpa ini, user bisa akses /courses/1/assignments/999 meski assignment 999 bukan milik course 1
    Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])
        ->name('assignments.show')
        ->scopeBindings();
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    Route::post('/assignments/{assignment}/submissions', [SubmissionController::class, 'store'])->name('submissions.store');
    Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download'])->name('submissions.download');
    Route::post('/submissions/{submission}/grade', [SubmissionController::class, 'grade'])->name('submissions.grade');

    Route::resource('users', UserController::class);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});
