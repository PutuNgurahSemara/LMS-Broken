# Perbaikan 7 Masalah Routing, Security (IDOR), dan Middleware (Branch W05) #

1. Route ```/course/{course} ``` diletakkan diluar grup middleware ```auth```

a. masalah yang ditemukan 

Di berkas ```routes/web.php```, definisi route untuk melihat detail mata kuliah ```GET /courses/{course}``` diletakkan di luar blok pembungkus ```Route::middleware('auth')->group(...)```.   

b. Dampak

Pengguna yang belum terautentikasi/belum login (guest) dapat mengakses detail mata kuliah sensitif secara langsung hanya dengan mengetikkan URL atau ID mata kuliah di browser tanpa melewati proses otentikasi.

c. Bukti perbaikan

Memindahkan route detail mata kuliah ke dalam grup middleware ```auth```

sebelum: 
// Terletak di luar Route::middleware('auth')

```Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');```

sesudah: 

```Route::middleware('auth')->group(function () {
    // Dipindahkan ke dalam grup auth
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
});```
```
2. Celah Keamanan IDOR pada Download Submission

a. Masalah yang ditemukan
Pada method ```download()``` di ```app/Http/Controllers/SubmissionController.php```, sistem langsung mengunduh file submission tanpa memverifikasi hak akses pengguna yang sedang login.

b.Dampak 
Terjadi kerentanan Insecure Direct Object Reference (IDOR). Mahasiswa dapat mengunduh berkas jawaban tugas milik mahasiswa lain hanya dengan mengganti ID submission pada URL

c. Bukti perbaikan

Sebelum: 
Mahasiswa B (ID: 5) dapat mengunduh berkas tugas milik Mahasiswa A (Submission ID: 2):   
```curl -i -b "session_cookie=XYZ" http://localhost:8000/submissions/2/download```
```
output response: 
```HTTP/1.1 200 OK
Content-Type: application/pdf
Content-Disposition: attachment; filename="tugas_mahasiswa_a.pdf"```
```
sesudah:
Akses ditolak dengan status 403 Forbidden karena Mahasiswa B bukan pemilik tugas, dosen pengampu, maupun admin:

```curl -i -b "session_cookie=XYZ" http://localhost:8000/submissions/2/download```

Output response:
```HTTP/1.1 403 Forbidden
Content-Type: text/html; charset=UTF-8

{"message": "THIS ACTION IS UNAUTHORIZED."}```
```
3. Celah keamanan IDOR pada Download Material Pembelajaran

a. masalah yang ditemukan 
Method ```download()``` pada ```MaterialController.php``` tidak menjalankan validasi otorisasi ```Gate::authorize()```, dan ```MaterialPolicy.php``` belum memiliki aturan otorisasi khusus unduh berkas.

b. Dampak
Pengguna yang tidak terdaftar pada suatu mata kuliah (unenrolled student) dapat mengunduh materi pembelajaran rahasia/terbatas milik kelas lain secara ilegal.

c. Bukti Perbaikan 

sebelum: 
```// MaterialController.php (Tidak ada proteksi Gate)
public function download(Material $material)
{
    return Storage::download($material->file_path);
}```
```
Sesudah: 
```// MaterialPolicy.php
public function download(User $user, Material $material): bool
{
    $course = $material->course;
    return $user->role === 'admin' 
        || (int) $course->lecturer_id === (int) $user->id 
        || $course->students()->where('user_id', $user->id)->exists();
}

// MaterialController.php
public function download(Material $material)
{
    Gate::authorize('download', $material);
    return Storage::download($material->file_path);
}```
```
4. Nested Route Tanpa Scope Bindings pada Assignment

a. Masalah Yang Ditemukan

\Route bertingkat  ```/courses/{course}/assignments/{assignment}``` di ```routes/web.php``` tidak dibungkus oleh ````scopeBindings()```, serta method ```show()``` di ```AssignmentController.php``` hanya menerima parameter $assignment. 

b. Dampak

Terjadi implicit binding mismatch di mana assignment milik Course A dapat dibuka menggunakan URL dari Course B, serta memicu kesalahan pemetaan URL pada framework. 

c. Bukti Perbaikan

Sebelum:

```PHP// routes/web.php
Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');

// AssignmentController.php
public function show(Assignment $assignment) { ... }```
```
Sesudah:

```PHP// routes/web.php
Route::scopeBindings()->group(function () {
    Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
});

// AssignmentController.php
public function show(Course $course, Assignment $assignment) { ... }```
```
5. Middleware Didaftarkan di Berkas yang Salah (Kernel.php)

a. Masalah Yang Ditemukan

Alias middleware ```'role'``` didaftarkan pada properti ````$middlewareAliases``` di ```app/Http/Kernel.php```.  

b. Dampak Pada arsitektur Laravel 11/12, pendaftaran middleware di ```Kernel.php``` diabaikan sehingga panggilan ```middleware('role:dosen')``` gagal/tidak memproteksi endpoint sama sekali.  

c. Bukti Perbaikan
 
 Sebelum:
 
 ```PHP// app/Http/Kernel.php
protected $middlewareAliases = [
    'role' => \App\Http\Middleware\RoleMiddleware::class,
];```
```
Sesudah:

```PHP// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);
})```
```
6. Nama Route Bentrok dan Memicu ```RouteNotFoundException (courses.index)```

a. Masalah Yang Ditemukan

Daftar mata kuliah dipisah menjadi nama route ```lecturer.courses.index``` dan ```student.courses.index```, sementara nama route umum ```courses.index``` tidak didefinisikan.  

 b. Dampak
 
 Aplikasi mengalami crash dengan error ```Symfony\Component\Routing\Exception\RouteNotFoundException: Route [courses.index] not defined```
 saat pengguna menekan tombol Kembali di halaman detail mata kuliah. 
 
c. Bukti Perbaikan

Sebelum:

```PHPRoute::get('/lecturer/courses', [CourseController::class, 'index'])->middleware('role:dosen')->name('lecturer.courses.index');
Route::get('/student/courses', [CourseController::class, 'index'])->middleware('role:mahasiswa')->name('student.courses.index');```
```
Sesudah:

```PHP// Menambahkan nama route umum courses.index
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');

Route::get('/lecturer/courses', [CourseController::class, 'index'])->middleware('role:dosen')->name('lecturer.courses.index');
Route::get('/student/courses', [CourseController::class, 'index'])->middleware('role:mahasiswa')->name('student.courses.index');```
```
7. Aksi Destruktif Hapus Materi Menggunakan HTTP Method GET

a. Masalah Yang Ditemukan

Tautan hapus materi pada berkas ```resources/views/courses/show.blade.php``` dibuat menggunakan elemen tag ```<a>``` berbasis method HTTP GET.   

b. Dampak

Rentan terhadap serangan Cross-Site Request Forgery (CSRF) dan risiko penghapusan materi secara tidak sengaja oleh proses prefetching atau web crawler mesin pencari.  

 c. Bukti Perbaikan

 Sebelum:
 ````Blade<!-- Menggunakan tag <a> (GET) -->
<a href="{{ route('materials.destroy', $material) }}" onclick="return confirm('Hapus materi ini?')" class="text-red-600 text-xs hover:underline">Hapus</a>```

````
Sesudah:
```Blade<!-- Menggunakan form (POST dengan override DELETE dan CSRF token) -->
<form action="{{ route('materials.destroy', $material) }}" method="POST" class="inline" onsubmit="return confirm('Hapus materi ini?')">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-red-600 text-xs hover:underline bg-transparent border-0 p-0 cursor-pointer">
        Hapus
    </button>
</form>```