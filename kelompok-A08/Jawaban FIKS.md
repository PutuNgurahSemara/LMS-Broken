## Masalah yang ditemukan ##

## 1. Route Saling Menutupi (Kelompok A-08 - Putri)
Kode yang bermasalah berada pada folder `Routes/web.php` pada baris `Route::get('/courses/{id}', ...)` berada sebelum `Route::get('/courses/create', ...)`, laravel membaca baris dari atas ke bawah jika `Route::get('/courses/{id}', ...)` ditaruh duluan pada saat membuka URL `Route::get('/courses/create', ...)` laravel akan menganggap `create` akan dianggap sebagai isi dari {id}. Halaman `create` tidak akan pernah diakses buka, akan mengalami error, perbaikan yang dilakukan mengubah urutan penulisan dengan memindahkan `Route::get('/courses/create', ...)` ke atas `Route::get('/courses/{id}', ...)`  
- Kode sebelum perbaikan : 
``Route::get('/courses/{id}', [CourseController::class, 'show'])->name('courses.show');``
``Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');``
- kode setelah perbaikan : 
``Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');``
``Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');``

## 2. HTTP method salah (Kelompok A-08 Fatimah)
Kode yang bermasalah berada pada folder Routes/web.php, permasalahan method pada penghapusan data menggunakan method `GET` padahal operasi menghapus seharusnya menggunakan `DELETE`. `GET`seharusnya digunakan untuk mengambil atau menampilkan data, bukan untuk mengubah atau menghapus data. Jika penghapus menggunakan `GET`, URL tersebut dapat terpanggil hanya dengan membuka link, refresh, crawler, atau mekanisme otomatis lain sehingga data berpotensi terhapus tanpa sengaja 
-  kode sebelum perbaikan : 
`Route::get('/courses/{id}/delete', [CourseController::class, 'destroy'])->name('courses.destroy.broken');` 

- kode setelah perbaikan : 
`Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');` 

## 3. URL hardcode (1) (Kelompok A-08 Sarah)
Kode yang bermasalah berada pada folder `Resource/views/course/index.blade.php`, permasalahan URL yang ditulis masih manual (HARDCODED) padahal dari laravel sendiri menyediakan fitur NAMED ROUTE. Jika menggunakan /courses/ apabila struktru URL berubah, maka tautan dapat rusak dan harus diperbaiki secara manual di banyak tempat dan resiko terjadinya human error tinggi karena risiko typo yang tinggi 
- kode sebelum perbaikan : 
`<a href="/courses/{{ $course['id'] }}">`

- kode setelah perbaikan : 
`<a href="{{ route('courses.show', $course['id']) }}">` 

## 4. URL Hardcore (2) ( Kelompok A-08 Sarah dan Fatimah)
Kode yang bermasalah berada pada `Resource/views/layout.blade.php` permasalahan ditermuka pada lane 14 dikode tersebut tautan navigasi menuju halaman mata  kuliah ditulis secara langsung karena masih menggunakan “courses”.Jika suatu saat URL utama berubah (misalnya diganti dari courses menjadi mata-kuliah di file rute), tautan navigasi di layout ini akan rusak/rusak ( error 404 ) . 
- kode sebelumm perbaikan : 
`<a href="/courses" class="hover:underline">Mata Kuliah</a>`

- kode setelah perbaikan : 
`<a href="{{ route('courses.index') }}" class="hover:underline">Mata Kuliah</a>`

## 5. Satu XSS (Kelompok A-08 Syarifah Nazwa)
Kode yang bermsalah berada pada folder `Resource/views/courses/show.blade.php `pada line 9, halaman detail mata kuliah, ditemukan penggunaan {!! !!} untuk untuk menampilkan data description. Sintaks tersebut menampilkan isi data sebagai HTML mentah tanpa proses escaping. Penggunaan {!! !!} menampilkan isi description sebagai HTML mentah tanpa escaping. Jika data mengandung script berbahaya, script tersebut dapat dieksekusi di browser pengguna dan berpotensi digunakan untuk memanipulasi halaman atau mengambil informasi dalam konteks sesi pengguna. 
- kode sebelum perbaikan : 
`<div class="prose">
    {!! $course['description'] !!}
</div>` 

- kode setelah perbaikan : 
<div class="prose">
    {{ $course['description'] }}
</div>

## 6. Satu Logika yang seharusnya tidak berada di view  (Kelompok A-08 Putri)
Kode yang bermasalah berada pada folder `Resource/views/courses/index.blade.php`. Logika pemrosesan data berada pada folder yang salah seharusnya berada di controller, Jika logika berada pada berkas tersebut  Akan membebani proses tampilan dan dan melanggar prinsip MVC yang dimana views harusnya bertugas menampilkan data bukan mengolah data. Pindahkan logika ke sesuai dengan file yang benar yaitu Course Controller, jadi data akan diolah oleh controller dan hasil nya akan dikirimkan ke view untuk ditampilkan.
Kode logika:  

- Kode Logika nya :
 @php
        $activeCourses = array_filter($courses, function($c) {
            return $c['status'] === 'active';
        });
    @endphp

## Kesimpulan 
Berdasarkan proses perbaikan yang telah dilakukan, pada awalnya kami menemukan beberapa bagian kode yang perlu diperbaiki dan ketika kode tersebut dijalankan, aplikasi Laravel sempat mengalami error sehingga kami mengira bahwa kesalahan tersebut disebabkan oleh perubahan kode yang dilakukan. Namun, setelah dilakukan pengecekan lebih lanjut, ternyata penyebab utama aplikasi tidak dapat ditampilkan adalah karena APP_KEY pada file .env belum tersedia. Setelah APP_KEY ditambahkan, aplikasi Laravel dapat dijalankan dan halaman web dapat ditampilkan dengan baik.
Hal tersebut menunjukkan bahwa masalah pada kode yang ditemukan sebelumnya tidak secara langsung menyebabkan tampilan website menjadi error. Beberapa kesalahan tersebut lebih berkaitan dengan alur kerja, keamanan, dan struktur kode di balik aplikasi, seperti kesalahan urutan route, penggunaan HTTP method yang tidak sesuai, penggunaan URL hardcode, potensi XSS, serta logika pengolahan data yang ditempatkan di View. Walaupun secara tampilan website masih dapat berjalan setelah APP_KEY ditambahkan, kesalahan-kesalahan tersebut tetap perlu diperbaiki karena dapat menimbulkan masalah pada fungsionalitas, keamanan, dan pemeliharaan aplikasi di kemudian hari.
Dengan demikian, dapat disimpulkan bahwa APP_KEY merupakan bagian penting agar aplikasi Laravel dapat berjalan, sedangkan enam masalah kode yang ditemukan merupakan masalah pada implementasi dan alur aplikasi yang tidak selalu langsung terlihat dari tampilan website. Oleh karena itu, perbaikan kode tetap diperlukan meskipun website sudah dapat ditampilkan dengan baik.
