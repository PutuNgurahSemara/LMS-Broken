# Laporan Bug Fix API KampusLMS - Broken Week 6

Telah ditemukan dan diperbaiki 8 error yang sengaja disisipkan sesuai dengan tantangan. Seluruh perbaikan telah diverifikasi dengan serangkaian test pada `tests/Feature/BrokenWeek6Test.php` serta bawaan `tests/Feature/ApiTest.php` yang semuanya telah LULUS (Passed).

### Setup Token untuk Pengujian Curl
Untuk mempraktekkan perintah curl di bawah ini, dapatkan token terlebih dahulu:
```bash
curl -s -X POST http://lms-broken.test/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gmail.com", "password":"password"}'
```
Simpan token yang muncul dan gunakan sebagai `[TOKEN]` pada perintah di bawah.

---

### Error #1 - Endpoint store mengembalikan 200 bukan 201
- **Lokasi:** `app/Http/Controllers/Api/CourseController.php:36` (method `store`)
- **Kategori:** C (API Resource dan format response)
- **Gejala:** Test `api store returns 201 created` gagal dengan pesan `Expected response status code [201] but received 200`.
- **Akar masalah:** Pembuatan resource baru (POST) standar HTTP seharusnya mengembalikan status 201 Created. Kode awal hanya menggunakan respon statis 200.
- **Perbaikan:**
  ```diff
  - return response()->json(new CourseResource($course), 200);
  + return (new CourseResource($course))->response()->setStatusCode(201);
  ```
- **Bukti curl:**
  ```bash
  curl -i -X POST http://lms-broken.test/api/courses \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer [TOKEN]" \
    -d '{"code":"T01", "name":"Tes", "sks":3, "lecturer_id":1, "status":"active"}'
  ```
  **Hasil setelah perbaikan:** Output akan menunjukan `HTTP/1.1 201 Created` (sebelumnya `HTTP/1.1 200 OK`).

---

### Error #2 - Endpoint show lupa diproteksi Sanctum
- **Lokasi:** `routes/api.php:8`
- **Kategori:** A / B (Autentikasi dan Sanctum / Route)
- **Gejala:** Endpoint dapat diakses tanpa token (bocor) karena route `show` berada di luar blok middleware `auth:sanctum`.
- **Akar masalah:** Route GET `/courses/{course}` diletakkan di luar `Route::middleware('auth:sanctum')`.
- **Perbaikan:** Memindahkan deklarasi route `show` ke dalam group yang terlindungi middleware.
  ```diff
  - Route::get('/courses/{course}', [CourseController::class, 'show']);
  + Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show'); // dipindah ke dalam group auth
  ```
- **Bukti curl:**
  ```bash
  curl -i -X GET http://lms-broken.test/api/courses/1 \
    -H "Accept: application/json"
  ```
  **Hasil setelah perbaikan:** Output mengembalikan `HTTP/1.1 401 Unauthorized` dengan pesan "Unauthenticated." (sebelumnya membocorkan data `HTTP/1.1 200 OK`).

---

### Error #3 - Unauthorized Action (403) dibalas dengan 401
- **Lokasi:** `bootstrap/app.php:24`
- **Kategori:** C / G (Format error / Otorisasi)
- **Gejala:** Jika otorisasi Gate gagal, kode kembaliannya 401 (Unauthenticated) bukan 403 (Forbidden).
- **Akar masalah:** Method `render` di exception handler memaksa status 401 untuk class `AuthorizationException`.
- **Perbaikan:**
  ```diff
  - return response()->json(['message' => 'This action is unauthorized.'], 401);
  + return response()->json(['message' => $e->getMessage() ?: 'This action is unauthorized.'], 403);
  ```
- **Bukti curl (asumsi token milik mahasiswa yang mencoba mengubah course):**
  ```bash
  curl -i -X PUT http://lms-broken.test/api/courses/1 \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer [TOKEN_MAHASISWA]" \
    -d '{"code":"T01", "name":"Ubah", "sks":3, "lecturer_id":1, "status":"active"}'
  ```
  **Hasil setelah perbaikan:** Output mengembalikan `HTTP/1.1 403 Forbidden` (sebelumnya `401 Unauthorized`).

---

### Error #4 - Rate Limiting API tidak dikonfigurasi
- **Lokasi:** `app/Providers/AppServiceProvider.php` dan `routes/api.php`
- **Kategori:** E (Rate limiting)
- **Gejala:** Tidak ada perlindungan rate limiting (seperti response header `X-RateLimit-Limit`).
- **Akar masalah:** Limiter `api` dan `login` tidak terdefinisi di provider dan middleware `throttle` tidak dipanggil di routes.
- **Perbaikan:** Menambahkan `RateLimiter::for` di `AppServiceProvider` (60 per menit umum, 5 per menit login) dan menambahkan middleware `throttle` pada `api.php`.
- **Bukti curl:**
  ```bash
  curl -i -X POST http://lms-broken.test/api/login \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@gmail.com", "password":"password"}'
  ```
  **Hasil setelah perbaikan:** Terdapat header tambahan seperti `X-RateLimit-Limit: 5` dan `X-RateLimit-Remaining: 4` di output HTTP-nya.

---

### Error #5 - Respons tanpa header Accept: application/json tetap mereturn redirect (302)
- **Lokasi:** `bootstrap/app.php`
- **Kategori:** A (Autentikasi dan Sanctum)
- **Gejala:** Mengakses endpoint `/api/courses` tanpa disisipi header `Accept: application/json` akan me-redirect browser/client ke form `/login` HTML.
- **Akar masalah:** Konfigurasi Laravel untuk memaksa API merespons format JSON ketika terjadi exception Unauthenticated belum di-setting (`shouldRenderJsonWhen`).
- **Perbaikan:** Menambahkan pengecualian di konfigurasi exceptions agar rute `api/*` secara otomatis dipaksa render ke JSON.
- **Bukti curl:**
  ```bash
  curl -i -X GET http://lms-broken.test/api/courses
  # (Tanpa header Accept: application/json)
  ```
  **Hasil setelah perbaikan:** Mengembalikan `HTTP/1.1 401 Unauthorized` dengan JSON payload (sebelumnya `HTTP/1.1 302 Found` meredirect ke login).

---

### Error #6 - Endpoint destroy mereturn status 200 bukan 204 No Content
- **Lokasi:** `app/Http/Controllers/Api/CourseController.php:66` (method `destroy`)
- **Kategori:** C (API Resource dan format response)
- **Gejala:** Permintaan Delete sukses mereturn body JSON dan status code 200 OK.
- **Akar masalah:** RESTful standard meminta metode DELETE sukses (tanpa response body) mendapatkan kode spesifik 204.
- **Perbaikan:**
  ```diff
  - return response()->json(['message' => 'Mata kuliah berhasil dihapus'], 200);
  + return response()->noContent();
  ```
- **Bukti curl:**
  ```bash
  curl -i -X DELETE http://lms-broken.test/api/courses/1 \
    -H "Accept: application/json" \
    -H "Authorization: Bearer [TOKEN]"
  ```
  **Hasil setelah perbaikan:** `HTTP/1.1 204 No Content` tanpa body (sebelumnya `200 OK`).

---

### Error #7 - Endpoint show/update membocorkan raw Model, tidak pakai Resource
- **Lokasi:** `app/Http/Controllers/Api/CourseController.php` (method `show` & `update`)
- **Kategori:** C (API Resource dan format response)
- **Gejala:** Payload respon JSON tidak difilter/dibungkus dan tidak ada selubung `"data": {...}`.
- **Akar masalah:** Controller mereturn `return $course;` langsung bukannya membungkus dalam spesifikasi Resource.
- **Perbaikan:**
  ```diff
  - return $course;
  + return new CourseResource($course);
  ```
- **Bukti curl:**
  ```bash
  curl -s -X GET http://lms-broken.test/api/courses/2 \
    -H "Accept: application/json" \
    -H "Authorization: Bearer [TOKEN]"
  ```
  **Hasil setelah perbaikan:** Output JSON kini rapi terstruktur dalam payload `{"data": {"id": 2, ...}}` yang mengikuti `CourseResource`.

---

### Error #8 - N+1 (Missing eager loading) pada endpoint index API
- **Lokasi:** `app/Http/Controllers/Api/CourseController.php:15` (method `index`)
- **Kategori:** D (Eager loading dan query)
- **Gejala:** Data object relasi `lecturer` dan field parameter count `students_count` kosong dan tidak nampak di respons API list.
- **Akar masalah:** `Course::where()->paginate()` langsung dieksekusi, padahal `CourseResource` mengandalkan `whenLoaded` dan `whenCounted`.
- **Perbaikan:**
  ```diff
  - $courses = Course::where('status', 'active')->paginate(10);
  + $courses = Course::with('lecturer')->withCount('students')->where('status', 'active')->paginate(10);
  ```
- **Bukti curl:**
  ```bash
  curl -s -X GET http://lms-broken.test/api/courses \
    -H "Accept: application/json" \
    -H "Authorization: Bearer [TOKEN]"
  ```
  **Hasil setelah perbaikan:** Properti object `"lecturer": {...}` dan properti integer `"students_count"` ter-load dan tampil meramaikan tiap item di dalam properti `"data"`.
