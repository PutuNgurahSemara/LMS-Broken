# Catatan Perbaikan Kelompok 4 | Minggu 10 - `Branch W10`

### Anggota Kelompok
1. Fitriyansyah Wicaksonoaji (10241033)
2. Hamzah Wiranata (10241035)
3. Jeshua Austin Daceka (10241037)

## 1. Listener masih belum menggunakan ShouldQueue

**Mengapa bermasalah?:** Method `handle()` langsung mengambil mahasiswa dan mengirim notification kepada mereka. Pemrosesan notification dilakukan menggunakan 'sync' (jadi tanpa queu) ketika event diproses. Jikalau banyak mahasiswa terdaftar, proses pembuatan job bisa terasa lambat.

**Langkah perbaikan:** Implementasikan `ShouldQueue` dan gunakan pengiriman kolektif.

**Sebelum:**

Lokasi file : `app/Listeners/SendAssignmentNotificationListener.php`
```php
use App\Events\AssignmentCreated;
use App\Notifications\NewAssignmentNotification;

class SendAssignmentNotificationListener
```


**Sesudah:**

Lokasi file : `app/Listeners/SendAssignmentNotificationListener.php`
```php
use App\Events\AssignmentCreated;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAssignmentNotificationListener implements ShouldQueue
```


## 2. Notification dikirim satu per satu dalam loop

**Mengapa bermasalah?:** berhubungan dengan masalah pertama, walaupun Listener diperbaiki, Job tetap memiliki pola pengiriman yang sama jika Job--yang saat ini--ini digunakan di bagian lain. Akhirnya, kode akan mengulang proses pengiriman untuk setiap mahasiswa.

**Langkah perbaikan:** mengganti isi `handle`

**Sebelum:**

Lokasi file : `app/Jobs/SendAssignmentNotificationJob.php`
```php
class SendAssignmentNotificationJob implements ShouldQueue
{
    public function handle(): void
    {
        $students = $this->assignment->course->students;

        foreach ($students as $student) {
            $student->notify(new NewAssignmentNotification($this->assignment));
        }
    }
}
```


**Sesudah:**

Lokasi file : `app/Jobs/SendAssignmentNotificationJob.php`
```php
class SendAssignmentNotificationJob implements ShouldQueue
{
    public function handle(): void
    {
        $students = $this->assignment->course->students;

        Notification::send(
            $students,
            new NewAssignmentNotification($this->assignment)
        );
    };
}

```

## 3. Payload notification memuat nilai mahasiswa

**Mengapa bermasalah?:** Jika memasukkan field yang isinya berisi 'nilai' mahasiswa, data akademik atau hal-hal penting lainnya bisa terlihat oleh penerima notifikasi yang tidak berhak melihat nilai mahasiswa lain. Karena data 'nilai' seluruh mahasiswa dimasukkan ke payload setiap notifikasi, hal tersebut bisa menimbulkan N+1 query 

N+1 query adalah masalah performa pada aplikasi yang menggunakan basis data relasional, di mana aplikasi melakukan 1 kueri utama untuk mengambil data utama, diikuti oleh N kueri tambahan untuk mengambil data relasi dari setiap baris hasil kueri tersebut.

Contohnya seperti, mau nampilkan daftar produk yang ada 100 beserta nama kategorinya:
1. 1 query utama: ngambil semua data produk
2. N query tambahan: muncul 1 kueri terpisah untuk setiap produk guna mengambil kategori produk.

Total kueri yang dikirim aplikasi jadi 101 kueri ke basis data. Bisa lemot aplikasimu, servermu juga.

**Langkah perbaikan:** Hapus `student_grades` dari payload. Cukup kirim informasi tentang assignment itu sendiri.

**Sebelum:**

Lokasi file : `app/Notifications/NewAssignmentNotification.php`
```php
class NewAssignmentNotification extends Notification implements ShouldQueue
{
    public function toArray(object $notifiable): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'title' => $this->assignment->title,
            'course_name' => $this->assignment->course->name,
            'due_at' => $this->assignment->due_at->toIso8601String(),
            'message' => "Tugas baru '{$this->assignment->title}' telah ditambahkan.",
            'student_grades' => $this->assignment->course->students->map(function ($student) {
                return [
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'score' => $student->submissions()->with('grade')->get()->pluck('grade.score')->filter()->avg(),
                ];
            }),
        ];
    }
}
```


**Sesudah:**

Lokasi file : `app/Notifications/NewAssignmentNotification.php`
```php
class NewAssignmentNotification extends Notification implements ShouldQueue
{
    public function toArray(object $notifiable): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'title' => $this->assignment->title,
            'course_name' => $this->assignment->course->name,
            'due_at' => $this->assignment->due_at->toIso8601String(),
            'message' => "Tugas baru '{$this->assignment->title}' telah ditambahkan.",
        ];
    }
}
```

## 4. Endpoint tandai-dibaca tidak memeriksa kepemilikan

**Mengapa bermasalah?:** User yang sudah login dapat mencoba ID notifikasi milik user lain. Karena pencarian tidak dibatasi based on pemiliknya, notifikasi tersebut berpotensi ditandai-dibaca oleh user yang salah.

**Langkah perbaikan:** Cari notifikasi melalui "relasi notifikasi" milik user yang sedang login.

**Sebelum:**

Lokasi file : `app/Http/Controllers/NotificationController.php`
```php
class NotificationController extends Controller
{
    public function markAsRead(Request $request, string $id)
    {
        $notification = \Illuminate\Notifications\DatabaseNotification::findOrFail($id);
        $notification->markAsRead();

        return redirect()->back()->with('success', 'Notifikasi ditandai telah dibaca.');
    }
}
```


**Sesudah:**

Lokasi file : `app/Http/Controllers/NotificationController.php`
```php
class NotificationController extends Controller
{
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
        ->notifications()
        ->whereKey($id)
        ->findOrFail();
        $notification->markAsRead();

        return redirect()->back()->with('success', 'Notifikasi ditandai telah dibaca.');
    }
}
```

## 5. Tidak ada migration untuk `failed_jobs`

**Mengapa bermasalah?:** Disaat `config/queue.php` sudah menggunakan konfigurasi `failed_jobs`, migrationnya malah belum ada. Ketika Job gagal setelah percobaan yang diizinkan, Laravel tidak dapat mencatat kegagalnnya ke tabel karena kan belum tersedia.

**Langkah perbaikan:** Cukup lakukan `php artisan migrate` karena `failed_jobs` sudah ada di dalam kerangka laravel 12, migrasi `0001_01_01_000002_create_jobs_table`

Lokasi file : `database/migrations/0001_01_01_000002_create_jobs_table`
```shell
INFO  Preparing database.  

  Creating migration table ....................... 341.17ms DONE

INFO  Running migrations.  

  0001_01_01_000001_create_cache_table ........... 270.34ms DONE
  0001_01_01_000002_create_jobs_table .............. 6.45ms DONE
  2026_01_01_000001_create_users_table ............ 75.22ms DONE
  2026_01_01_000002_create_courses_table .......... 65.56ms DONE
  2026_01_01_000003_create_course_user_table ...... 75.16ms DONE
  2026_01_01_000004_create_materials_table ......... 7.06ms DONE
  2026_01_01_000005_create_assignments_table ...... 10.89ms DONE
  2026_01_01_000006_create_submissions_table ...... 10.12ms DONE
  2026_01_01_000007_create_grades_table ........... 16.60ms DONE
  2026_01_01_000008_create_notifications_table ..... 8.97ms DONE
  2026_09_04_073629_create_personal_access_tokens_table  21.57ms DONE
```


## 6. Job tidak memiliki batas `tries`

**Mengapa bermasalah?:** Tanpa batas yang ditetapkan, perilaku retry tidak dinyatakan secara eksplisit oleh Job itu sendiri.

**Langkah perbaikan:** Tambahankan properti `$tries` dengan batas 3 kali.

**Sebelum:**

Lokasi file : `app/Jobs/SendAssignmentNotificationJob.php`
```php
class SendAssignmentNotificationJob implements ShouldQueue
{
    public int $timeout = 60;
};
```

**Sesudah:**

Lokasi file : `app/Jobs/SendAssignmentNotificationJob.php`
```php
class SendAssignmentNotificationJob implements ShouldQueue
{
    public int $tries = 3;
    public int $timeout = 60;
};
```

## 7. Event broadcast menggunakan channel publik

**Mengapa bermasalah?:** Pengunjung tidak harus terautentikasi atau punya otorasi channel untuk berlangganan. Kalau ternyata event membawa informasi assignment yang seharusnya hanya dilihat mahasiswa terdaftar atau dosen terkait (pengunjung yang terautentikasi dan punya otorisasi), orang di luar course juga berpotensi mendengarkan event tersebut.

**Langkah perbaikan:** Ganti `new Channel` menjadi `new privateChannel` terus tambahkan aturan otorisasi di `routes/channels.php`

**Sebelum:**

Lokasi file : `app/Events/AssignmentCreated.php`
```php
use Illuminate\Broadcasting\Channel;

public function broadcastOn(): array
    {
        return [
            new Channel('course.'.$this->assignment->course_id),
        ];
    }
```


**Sesudah:**

Lokasi file : `app/Events/AssignmentCreated.php`
```php
use Illuminate\Broadcasting\PrivateChannel;

public function broadcastOn(): array
    {
        return [
            new PrivateChannel('course.'.$this->assignment->course_id),
        ];
    }
```
```