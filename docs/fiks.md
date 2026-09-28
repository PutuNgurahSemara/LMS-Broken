## courses.show di luar auth, dan urutannya di atas courses.create 
Jawaban : 

File nya berada di `routes/web.php`
Akibatnya Tamu yang belum login bisa membuka detail course. Selain itu, GET /courses/create tertangkap route show karena terdaftar lebih dulu, dengan {course} = "create". Hasilnya 404 dan form tambah course tidak bisa dibuka. 

Kode sebelum perbaikan : 
```
Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');


Route::middleware('auth')->group(function () {
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
});
```

Kode setelah perbaikan : 
```
Route::middleware('auth')->group(function () {
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
});
```

## IDOR submission 
Jawaban :

File berada di `app/Http/Controllers/SubmissionController.php`
Akibatnya mahasiswa mana pun yang login bisa mengunduh tugas mahasiswa lain dengan mengganti ID di /submissions/{id}/download. 

Kode sebelum perbaikan : 
```
public function download(Submission $submission)
{
    if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
        abort(404, 'Berkas submission tidak ditemukan.');
    }


    return Storage::disk('local')->download($submission->file_path, $submission->original_name);
}
```

Kode setelah perbaikan : 
```
public function download(Request $request, Submission $submission)
{
    $user = $request->user();
    $course = $submission->assignment->course;


    $boleh = $user->role === 'admin'
        || (int) $submission->user_id === (int) $user->id
        || (int) $course->lecturer_id === (int) $user->id;


    abort_unless($boleh, 403, 'Anda tidak memiliki akses ke submission ini.');


    if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
        abort(404, 'Berkas submission tidak ditemukan.');
    }


    return Storage::disk('local')->download($submission->file_path, $submission->original_name);
}
```

## IDOR material 
Jawaban : 

Filenya berada `app/Http/Controllers/MaterialController.php`  Akibat nya pengguna mana pun yang login bisa mengunduh materi course yang tidak diikutinya dengan mengganti ID di /materials/{id}/download. 

Kode sebelum perbaikan : 
```public function download(Material $material)
{
    if ($material->type !== 'file' || ! $material->file_path || ! Storage::disk('local')->exists($material->file_path)) {
        abort(404, 'Berkas materi tidak ditemukan.');
    }


    return Storage::disk('local')->download($material->file_path, $material->original_name);
}
```

Kode setelah perbaikan : 
```
 public function download(Material $material)
{
    Gate::authorize('download', $material);


    if ($material->type !== 'file' || ! $material->file_path || ! Storage::disk('local')->exists($material->file_path)) {
        abort(404, 'Berkas materi tidak ditemukan.');
    }


    return Storage::disk('local')->download($material->file_path, $material->original_name);
}
```
- Tambahan Perbaikan app/Policies/MaterialPolicy.php 
```
 public function download(User $user, Material $material): bool
{
    $course = $material->course;


    return $user->role === 'admin'
        || (int) $course->lecturer_id === (int) $user->id
        || $course->students()->where('user_id', $user->id)->exists();
}
```

## Nested route tanpa scopeBindings 
Jawaban

Filenya berada di `routes/web.php` 
Akibatnya `/courses/1/assignments/99` bisa membuka assignment milik course lain, karena Laravel mencari {assignment} di seluruh tabel tanpa memeriksa apakah assignment itu milik course 1. 

Kode sebelum perbaikan : 
```
Route::post('/courses/{course}/materials', [MaterialController::class, 'store'])->name('materials.store');
Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');
Route::get('/materials/{material}/delete', [MaterialController::class, 'destroy'])->name('materials.destroy');


Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
```

Kode setelah perbaikan : 
```
Route::scopeBindings()->group(function () {
    Route::post('/courses/{course}/materials', [MaterialController::class, 'store'])
        ->name('materials.store');


    Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])
        ->name('assignments.store');


    Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])
        ->name('assignments.show');
});
```

Route download material dan delete material tidak perlu dimasukkan ke sini karena keduanya hanya menggunakan `{material}`, bukan nested `{course}/{material}`. 

## Alias role di berkas yang salah 

Jawaban : 

File nya berada di `app/Http/Kernel.php`
Akibatnya Middleware role tidak didaftarkan di tempat yang sesuai dengan struktur Laravel 12, sehingga alias role tidak seharusnya diletakkan di Kernel.php. 

Kode sebelum perbaikan :
```
<?php


namespace App\Http;


use Illuminate\Foundation\Http\Kernel as HttpKernel;


class Kernel extends HttpKernel
{
    protected $middlewareAliases = [
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ];
}
```

Kode setelah perbaikan 
```
 <?php


namespace App\Http;


use Illuminate\Foundation\Http\Kernel as HttpKernel;


class Kernel extends HttpKernel
{
}
```
- Jadi alias role dihapus dari `Kernel.php`.Ke Middleware tambahkan ini:

```
use App\Http\Middleware\RoleMiddleware;
dan  Kemudian pada bagian withMiddleware, tambahkan alias: 
 ->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => RoleMiddleware::class,
    ]);
})
```

Tambahkan ke `bootstrap/app.php `

```
use App\Http\Middleware\RoleMiddleware;
```

## Nama courses.index ganda 
Jawaban 

File nya berada di `routes/web.php, pemanggil di resources/views/` dan `app/Http/Controllers/` Akibat nya Dosen dan mahasiswa sama-sama memiliki nama route courses.index. Nama route seharusnya unik agar Laravel tidak bingung saat route('courses.index') dipanggil. 

Kode sebelum perbaikan : 
```
baris 28: 

Route::get('/lecturer/courses', [CourseController::class, 'index'])
    ->middleware('role:dosen')
    ->name('courses.index');


baris 29: 

Route::get('/student/courses', [CourseController::class, 'index'])
    ->middleware('role:mahasiswa')
    ->name('courses.index');
```

Kode setelah perbaikan : 
```
untuk dosen :  

 Route::get('/lecturer/courses', [CourseController::class, 'index'])
    ->middleware('role:dosen')
    ->name('lecturer.courses.index');

untuk mahasiswa : 

 Route::get('/student/courses', [CourseController::class, 'index'])
    ->middleware('role:mahasiswa')
    ->name('student.courses.index');
```
masing-masing role harus mempunyai nama route yang berbeda. 

## Hapus materi pakai GET 

Jawaban : 

File nya berada pada `routes/web.php`, view yang menampilkan tombol hapus 
Akibat nya Menghapus data menggunakan GET tidak tepat karena GET seharusnya digunakan untuk mengambil/menampilkan data, bukan melakukan penghapusan. 

Kode sebelum perbaikan : 
```
Route::get('/materials/{material}/delete', [MaterialController::class, 'destroy'])->name('materials.destroy');
```

Kode setelah perbaikan 
```
Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');
```
Filenya berada `resources/views/courses/show.blade.php`
Akibatnya Data dapat terhapus melalui request GET, sehingga aksi penghapusan tidak menggunakan method HTTP yang sesuai dan lebih berisiko dijalankan secara tidak sengaja.

Kode sebelum perbaikan : 
```
 <a href="{{ route('materials.destroy', $material) }}"
   onclick="return confirm('Hapus materi ini?')"
   class="text-red-600 text-xs hover:underline">
    Hapus
</a>
```

Kode setelah perbaikan : 
```
@can('delete', $material)
<form action="{{ route('materials.destroy', $material) }}" method="POST"
      onsubmit="return confirm('Hapus materi ini?')">
    @csrf
    @method('DELETE')


    <button type="submit" class="text-red-600 text-xs hover:underline">
        Hapus
    </button>
</form>
@endcan
``` 








