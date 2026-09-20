# Perbaikan 7 Masalah Migrasi, Model, dan Controller (Branch `w03`)

## 1. Urutan migrasi salah, `courses` dijalankan sebelum `users`

### a. Masalah Yang Ditemukan

di folder migrations ada urutan migrasi yang kebalik, dimana ```2026_01_01_000001_create_courses_table.php``` yaitu tabel courses dibuat terlebih dahulu sebelum tabel dari users dibuat ```2026_01_01_000002_create_users_table.php```


### b. Dampak

Tabel courses memiliki foreign key lecturer_id yang merujuk ke tabel users. Jika migrasi courses dijalankan sebelum migrasi users, MySQL akan menolak dengan error "foreign key constraint incorrectly formed", karena relasi tidak dapat dibuat ke tabel yang belum ada. Pada MySQL, error ini muncul langsung saat  artisan migrate dijalankan

### c. Bukti Perbaikan

Untuk perbaikan yang dilakukan adalah dengan menukar urutan timestamp kedua file migrasi

Sebelum:
```
2026_01_01_000001_create_courses_table.php
2026_01_01_000002_create_users_table.php
```

Sesudah:
```
2026_01_01_000001_create_users_table.php
2026_01_01_000002_create_courses_table.php
```

---

## 2. Tidak Ada Unique Constraint pada Tabel `course_user`

### a. Masalah Yang Ditemukan

Pada file migrasi tabel pivot `course_user` (untuk relasi many-to-many antara `courses` dan `users`), tidak ada unique constraint yang mencegah duplikasi data enrollment. Kombinasi `course_id` dan `user_id` dibiarkan bisa muncul lebih dari satu kali.

Sebelum:

```
Schema::create('course_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('course_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamp('enrolled_at')->useCurrent();
    $table->timestamps();
});
```

### b. Dampak

Tanpa constraint ini, satu mahasiswa bisa ter-enroll berkali-kali ke mata kuliah yang sama. Hal ini bisa terjadi karena:

- User double klik tombol "Daftar"
- Dua request submit bersamaan

Akibatnya:

- Data enrollment duplikat di database
- Jumlah peserta mata kuliah di laporan jadi salah hitung
- Query yang mengasumsikan "satu mahasiswa = satu baris per course" akan menghasilkan data yang keliru

### c. Bukti Perbaikan

Ditambahkan unique constraint pada kombinasi kolom `course_id` dan `user_id`.

Sebelum:

```
Schema::create('course_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('course_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamp('enrolled_at')->useCurrent();
    $table->timestamps();
});
```

Sesudah:

```
Schema::create('course_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('course_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamp('enrolled_at')->useCurrent();
    $table->timestamps();

    $table->unique(['course_id', 'user_id']);
});
```



---

## 3. `down()` Kosong di `materials_table`

### a. Masalah Yang Ditemukan

Pada file migrasi `materials_table`, method `down()` dibiarkan kosong tanpa perintah apapun. Padahal method ini seharusnya berisi perintah untuk membatalkan migrasi (drop tabel).

Sebelum:

```
public function down(): void
{
}
```

### b. Dampak

Kalau ada yang menjalankan `migrate:rollback`, tabel `materials` tidak akan pernah terhapus meskipun Laravel mencatat migrasi tersebut sebagai "rolled back". State migrasi dan kondisi database jadi tidak sinkron, perintah `migrate:fresh` atau `migrate:refresh` berikutnya bisa gagal karena tabel `materials` masih ada padahal Laravel mengira belum ada.

### c. Bukti Perbaikan

Ditambahkan perintah `Schema::dropIfExists('materials')` pada method `down()`.

Sebelum:

```
public function down(): void
{
}
```

Sesudah:

```
public function down(): void
{
    Schema::dropIfExists('materials');
}
```

---

## 4. `onDelete` Keliru di `courses.lecturer_id`

### a. Masalah Yang Ditemukan

Pada file migrasi tabel `courses`, foreign key `lecturer_id` yang merujuk ke tabel `users` menggunakan `cascadeOnDelete()`. Ini berarti menghapus user (dosen) akan otomatis menghapus semua course yang diampunya.

Sebelum:

```
$table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
```

### b. Dampak

Dengan `cascadeOnDelete`, menghapus satu akun dosen otomatis menghapus semua mata kuliah yang diampunya. Karena `materials` dan `assignments` juga cascade ke `courses`, efeknya berantai, ikut menghapus seluruh materi, tugas, submission, dan nilai mahasiswa yang terkait. Satu klik hapus akun dosen berpotensi memusnahkan riwayat akademik banyak mahasiswa sekaligus, tanpa cara mengembalikannya. `restrictOnDelete` menolak penghapusan dosen selama dia masih punya mata kuliah aktif, memaksa proses pemindahan course terlebih dahulu.

### c. Bukti Perbaikan

Mengganti `cascadeOnDelete()` menjadi `restrictOnDelete()` pada foreign key `lecturer_id`.

Sebelum:

```
$table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
```

Sesudah:

```
$table->foreignId('lecturer_id')->constrained('users')->restrictOnDelete();
```
---

## 5. Unique composite hilang di `submissions_table`

## 5. Unique Composite Hilang di `submissions_table`

### a. Masalah Yang Ditemukan

Pada file migrasi tabel `submissions`, tidak ada unique constraint pada kombinasi kolom `assignment_id` dan `user_id`. Akibatnya satu mahasiswa bisa memiliki lebih dari satu baris submission untuk tugas yang sama.

Sebelum:

```
Schema::create('submissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('file_path')->nullable();
    $table->text('notes')->nullable();
    $table->timestamp('submitted_at');
    $table->timestamps();
});
```

### b. Dampak

Tanpa constraint ini, mahasiswa bisa submit tugas yang sama berkali-kali sebagai baris-baris terpisah. Dosen jadi bingung submission mana yang harus dinilai, dan karena `grades` terhubung ke satu `submission_id` spesifik, penilaian bisa salah sasaran, menilai submission lama alih-alih submission terbaru yang dimaksud mahasiswa.

### c. Bukti Perbaikan

Ditambahkan unique constraint pada kombinasi kolom `assignment_id` dan `user_id`.

Sebelum:

```
Schema::create('submissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('file_path')->nullable();
    $table->text('notes')->nullable();
    $table->timestamp('submitted_at');
    $table->timestamps();
});
```

Sesudah:

```
Schema::create('submissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('file_path')->nullable();
    $table->text('notes')->nullable();
    $table->timestamp('submitted_at');
    $table->timestamps();

    $table->unique(['assignment_id', 'user_id']);
});
```

---

## 6. `$guarded = []` di Model `User`

### a. Masalah Yang Ditemukan

Pada model `User`, properti `$guarded = []` digunakan. Ini berarti tidak ada satupun kolom yang dilindungi dari mass assignment, sehingga semua kolom di tabel `users` bisa diisi bebas.

Sebelum:

```
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = [];
    ...
}
```

### b. Dampak

`$guarded = []` berarti tidak ada satupun kolom yang dilindungi dari mass assignment. Semua kolom di tabel `users`, termasuk `role` dan `email_verified_at`, bisa diisi bebas lewat `User::create($request->all())`. Ini adalah celah keamanan nyata (mass assignment vulnerability): kalau ada request registrasi yang menyisipkan field tambahan `role=admin`, mahasiswa biasa berpotensi menjadikan dirinya admin hanya dengan menambahkan satu field di request, tanpa perlu akses backend sama sekali.

### c. Bukti Perbaikan

Mengganti `$guarded = []` dengan `$fillable` yang membatasi kolom yang boleh diisi user lewat form biasa. Kolom `role` sengaja tidak dimasukkan ke `$fillable` sehingga hanya bisa diubah lewat logic backend yang terkontrol.

Sebelum:

```
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = [];
    ...
}
```

Sesudah:

```
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'nim_nip'];
    ...
}
```

---

## 7. `$request->all()` Tanpa Validasi di `UserController`

### a. Masalah Yang Ditemukan

Pada `UserController`, method `store()` dan `update()` menggunakan `$request->all()` untuk mengambil seluruh data request tanpa validasi dan tanpa filter.

Sebelum:

```php
public function store(Request $request)
{
    User::create($request->all());
    ...
}

public function update(Request $request, User $user)
{
    $user->update($request->all());
    ...
}
```

### b. Dampak

`$request->all()` mengambil seluruh data request apa adanya tanpa validasi dan tanpa filter. Ini punya dua konsekuensi:

- Tidak ada validasi sama sekali, field wajib bisa kosong, format email tidak diperiksa, password bisa terlalu pendek.
- Controller tidak punya lapisan pertahanan sendiri, perbaikan di poin 6 (`$fillable`) menutup celah mass assignment saat ini, tapi kalau suatu saat model berubah lagi atau developer lain menambahkan kolom sensitif baru, controller ini langsung kembali rentan tanpa disadari siapapun.

Validasi eksplisit di controller adalah lapisan pertahanan kedua (defense in depth), sistem yang aman tidak boleh bergantung hanya pada satu lapisan proteksi.

### c. Bukti Perbaikan

Menambahkan validasi eksplisit pada method `store()` dan `update()` menggunakan `$request->validate()`.

Sebelum:

```php
public function store(Request $request)
{
    User::create($request->all());
    ...
}

public function update(Request $request, User $user)
{
    $user->update($request->all());
    ...
}
```

Sesudah:

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => ['required', Password::min(8)],
        'nim_nip' => 'nullable|string|unique:users,nim_nip',
    ]);

    $validated['password'] = bcrypt($validated['password']);

    User::create($validated);
    ...
}

public function update(Request $request, User $user)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'nim_nip' => 'nullable|string|unique:users,nim_nip,' . $user->id,
    ]);

    $user->update($validated);
    ...
}
```
---

