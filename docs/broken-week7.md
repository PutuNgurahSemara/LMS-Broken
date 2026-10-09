# Catatan Perbaikan Broken Week 7

Baseline Git bersih pada `W07` di `25bf714` (`w07: repo cacat minggu 7`). Run
awal sembilan regression test menghasilkan tujuh kegagalan dan dua yang lulus;
test email enumeration kemudian dikoreksi agar membandingkan pesan sebelum
session test berikutnya, dan gagal dengan dua pesan berbeda. Session test
mengisolasi pemanggilan controller dan gagal ketika baris `regenerate()` dilepas.
Pemeriksaan route awal menampilkan 43 route. Migrasi dan seeding baseline
berhasil di SQLite in-memory. Baseline suite juga gagal pada satu assertion
scaffold lama yang mengharapkan `GET /` berstatus `200`, walau route root memang
redirect ke login; assertion itu diperbarui tanpa mengubah perilaku route.

## Error #1 - Otorisasi hilang pada perubahan mata kuliah

- Lokasi: `app/Http/Controllers/CourseController.php:82` (`update`) dan `:100` (`destroy`)
- Kategori: A - Policy dan otorisasi
- Gejala: Dosen selain pemilik mata kuliah dapat mengubah maupun menghapusnya.
- Akar masalah: Kedua endpoint mutasi tidak memanggil `CoursePolicy`, meskipun
  controller lain dan tombol Blade sudah menggunakan ability terkait.
- Sebelum:
  ```php
  public function update(Request $request, Course $course)
  {
      ...
  public function destroy(Course $course)
  {
      $course->delete();
  }
  ```
- Sesudah:
  ```php
  public function update(Request $request, Course $course)
  {
      Gate::authorize('update', $course);
      ...
  }

  public function destroy(Course $course)
  {
      Gate::authorize('delete', $course);
      $course->delete();
  }
  ```
- Bukti: `test_non_owner_lecturer_cannot_update_or_delete_a_course` menerima
  `302` pada baseline saat berharap `403`; setelah perbaikan kedua aksi
  mengembalikan `403` dan mata kuliah tetap ada.

## Error #2 - Otorisasi hapus materi hilang

- Lokasi: `app/Http/Controllers/MaterialController.php:68` (`destroy`)
- Kategori: A - Policy dan otorisasi
- Gejala: Dosen lain dapat menghapus materi milik sebuah mata kuliah.
- Akar masalah: Endpoint menghapus berkas dan record tanpa memeriksa
  `MaterialPolicy::delete`.
- Sebelum:
  ```php
  public function destroy(Material $material)
  {
      $course = $material->course;
  ```
- Sesudah:
  ```php
  public function destroy(Material $material)
  {
      Gate::authorize('delete', $material);
      $course = $material->course;
  ```
- Bukti: `test_non_owner_lecturer_cannot_delete_course_material` menerima
  `302` pada baseline; sesudah perbaikan menerima `403` dan record materi tetap
  ada.

## Error #3 - Policy submission selalu mengizinkan akses

- Lokasi: `app/Policies/SubmissionPolicy.php:10` (method `before` pada baseline)
- Kategori: A - Policy dan otorisasi
- Gejala: Dosen yang bukan pengampu assignment dapat menilai submission.
- Akar masalah: `before()` selalu mengembalikan `true`, sehingga ability
  `grade`, `view`, dan lainnya tidak pernah mencapai aturan masing-masing.
- Sebelum:
  ```php
  public function before(User $user, string $ability): ?bool
  {
      return true;
  }
  ```
- Sesudah:
  ```php
  class SubmissionPolicy
  {
      public function viewAny(User $user): bool
      {
          return in_array($user->role, ['admin', 'dosen']);
      }
  }
  ```
- Bukti: `test_submission_policy_does_not_allow_an_unrelated_lecturer_to_grade`
  menerima `302` pada baseline; sesudah perbaikan menerima `403` dan tidak
  membuat record nilai.

## Error #4 - Daftar pengguna terbuka untuk semua role

- Lokasi: `app/Http/Controllers/UserController.php:13` (`index`)
- Kategori: A - Policy dan otorisasi
- Gejala: Mahasiswa dapat membuka direktori yang memuat nama, email, NIM/NIP,
  dan role pengguna lain.
- Akar masalah: `UserPolicy::viewAny` hanya mengizinkan admin, tetapi action
  index tidak pernah meminta otorisasi.
- Sebelum:
  ```php
  public function index(Request $request)
  {
      $query = User::query();
  ```
- Sesudah:
  ```php
  public function index(Request $request)
  {
      Gate::authorize('viewAny', User::class);
      $query = User::query();
  ```
- Bukti: `test_mahasiswa_cannot_view_user_directory` mendapat `200` pada
  baseline; sesudah perbaikan mendapat `403`.

## Error #5 - Pengguna dapat mengganti role sendiri

- Lokasi: `app/Http/Controllers/UserController.php:94` (`update`)
- Kategori: A - Policy dan otorisasi
- Gejala: Pengguna yang mengedit profil dapat mengirim `role=admin`.
- Akar masalah: Validasi input role dilakukan, tetapi hasil
  `UserPolicy::updateRole` tidak diperiksa sebelum perubahan disimpan.
- Sebelum:
  ```php
  if ($request->has('role')) {
      $request->validate(['role' => 'required|in:admin,dosen,mahasiswa']);
      $user->role = $request->role;
  }
  ```
- Sesudah:
  ```php
  if ($request->has('role')) {
      Gate::authorize('updateRole', $user);
      $request->validate(['role' => 'required|in:admin,dosen,mahasiswa']);
      $user->role = $request->role;
  }
  ```
- Bukti: `test_user_cannot_change_own_role` menerima `302` pada baseline;
  sesudah perbaikan menerima `403` dan role di database tetap `mahasiswa`.

## Error #6 - Regenerasi session tidak eksplisit setelah login

- Lokasi: `app/Http/Controllers/AuthController.php:36` (`login`)
- Kategori: E - Keamanan
- Gejala: Kode controller tidak meregenerasi session setelah autentikasi
  berhasil, sehingga perlindungan session-fixation bergantung pada detail
  implementasi guard.
- Akar masalah: Pemanggilan `session()->regenerate()` hilang dari jalur login.
- Sebelum:
  ```php
  if (Auth::attempt($credentials, $request->boolean('remember'))) {
      RateLimiter::clear($throttleKey);
      return redirect()->intended(route('dashboard'));
  }
  ```
- Sesudah:
  ```php
  if (Auth::attempt($credentials, $request->boolean('remember'))) {
      RateLimiter::clear($throttleKey);
      $request->session()->regenerate();
      return redirect()->intended(route('dashboard'));
  }
  ```
- Bukti: `test_successful_login_regenerates_the_session_id` menggunakan auth
  driver tiruan untuk mengisolasi tanggung jawab controller. Test gagal ketika
  baris ini dilepas (ID cookie tidak berubah) dan lulus sesudah dipulihkan.
  Dengan guard session Laravel 12 standar, login juga sudah meregenerasi ID
  melalui `SessionGuard`; baris controller memulihkan perlindungan eksplisit
  yang hilang dari implementasi ini.

## Error #7 - Pesan login membocorkan keberadaan email

- Lokasi: `app/Http/Controllers/AuthController.php:18` (`login`)
- Kategori: E - Keamanan
- Gejala: Email yang tidak terdaftar menghasilkan pesan berbeda dari email
  terdaftar dengan password salah.
- Akar masalah: Pemeriksaan `User::where(...)->exists()` membuat respons
  autentikasi membedakan akun yang ada dan tidak ada.
- Sebelum:
  ```php
  if (! User::where('email', $credentials['email'])->exists()) {
      throw ValidationException::withMessages([
          'email' => 'Email tidak terdaftar.',
      ]);
  }
  // Jika email dikenal tetapi password salah:
  throw ValidationException::withMessages([
      'email' => 'Password yang Anda masukkan salah.',
  ]);
  ```
- Sesudah:
  ```php
  // Tidak memeriksa apakah email terdaftar sebelum Auth::attempt().
  if (Auth::attempt($credentials, $request->boolean('remember'))) {
      RateLimiter::clear($throttleKey);
      $request->session()->regenerate();
      return redirect()->intended(route('dashboard'));
  }
  throw ValidationException::withMessages([
      'email' => 'Email atau password yang Anda masukkan salah.',
  ]);
  ```
- Bukti: `test_login_error_does_not_disclose_whether_the_email_exists` gagal
  pada baseline dengan pesan “Email tidak terdaftar.” versus “Password yang
  Anda masukkan salah.”; setelah perbaikan pesan kedua kondisi sama.

## Error #8 - Route pembuatan mata kuliah di luar middleware auth

- Lokasi: `routes/web.php:27` (`courses.create`)
- Kategori: B - Route dan middleware
- Gejala: Pengunjung anonim mencapai controller dan menerima `403` dari Gate,
  bukan diarahkan ke login oleh middleware `auth`.
- Akar masalah: Route statis `courses.create` diletakkan di luar grup route
  terautentikasi.
- Sebelum:
  ```php
  Route::get('/courses/create', [CourseController::class, 'create'])
      ->name('courses.create');

  Route::middleware('auth')->group(function () {
      ...
  });
  ```
- Sesudah:
  ```php
  Route::middleware('auth')->group(function () {
      ...
      Route::get('/courses/create', [CourseController::class, 'create'])
          ->name('courses.create');
  });
  ```
- Bukti: `test_course_creation_page_requires_authentication` menerima `403`
  pada baseline; sesudah perbaikan pengunjung diarahkan ke route `login`.

## Error #9 - Policy memuat seluruh mahasiswa untuk cek enrollment

- Lokasi: `app/Policies/CoursePolicy.php:21` (`view`)
- Kategori: A - Policy dan otorisasi
- Gejala: Cek akses course memuat koleksi seluruh mahasiswa terdaftar hanya
  untuk mengetahui apakah satu mahasiswa tertentu terdaftar; bila policy
  dipakai berulang dalam daftar, tiap course melakukan lazy-load tambahan.
- Akar masalah: Properti relasi `$course->students` mematerialisasi koleksi
  relasi alih-alih menjalankan cek keberadaan di database.
- Sebelum:
  ```php
  return $course->students->pluck('id')->contains($user->id);
  ```
- Sesudah:
  ```php
  return $course->students()->where('user_id', $user->id)->exists();
  ```
- Bukti: `test_course_view_policy_checks_enrollment_with_an_exists_query`
  mendapati query `select users.* ...` pada baseline; sesudah perbaikan query
  enrollment menggunakan `exists` sehingga tidak memuat dan menghidrasi
  seluruh koleksi mahasiswa untuk cek satu pengguna; akses tetap menolak
  mahasiswa yang tidak terdaftar.

## Temuan tambahan di luar sembilan regresi

- `tests/Feature/ExampleTest.php` masih mengharapkan `GET /` mengembalikan
  `200`, padahal route root yang telah ada memang mengarahkan pengguna anonim
  ke halaman login. Assertion test disesuaikan ke redirect tersebut; perilaku
  aplikasi dan route tidak diubah.
- Baseline run tanpa `.env` gagal karena `APP_KEY` kosong, lalu menampilkan
  warning yang merujuk file `.env` yang tidak tersedia. Untuk menjalankan
  verifikasi lokal, `.env` dibuat dari `.env.example` dan key dihasilkan lewat
  Artisan. File ini diabaikan Git dan tidak termasuk perubahan kode.
- Log juga mencatat kegagalan `package:discover` sementara (`rename ... Access
  is denied`) saat dua proses `composer install` tumpang tindih pada setup.
  Setelah dependensi dan `.env` tersedia, migrasi, test, dan alur browser
  berjalan tanpa error baru di log.
