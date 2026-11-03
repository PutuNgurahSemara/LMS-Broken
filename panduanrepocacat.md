# Panduan Repo Cacat — kampuslms-broken

*SI2514024 — Pemrograman Web | Ganjil 2026/2027*
*Dokumen internal. Tidak dibagikan ke mahasiswa. Repo ini sendiri dibagikan; catatan ini tidak.*

---

## Apa ini

Tahap *FIX* pada modul mingguan mengharuskan mahasiswa memperbaiki proyek yang sudah dirusak lebih dulu. Kerusakan itu tidak ada di repo mahasiswa — ia hidup di repo terpisah kampuslms-broken, satu branch per minggu.

Dokumen ini adalah daftar induk seluruh branch beserta cacat yang harus ada di dalamnya. Sumbernya adalah teks tahap FIX pada modul mahasiswa: *jumlah dan jenis cacat sudah diberitahukan kepada mahasiswa*, jadi yang rahasia bukan daftarnya, melainkan lokasi persisnya di dalam kode.

Aturan yang menjaga tahap FIX tetap bermakna: *jumlah cacat selalu diberitahukan, letaknya tidak pernah.* Mahasiswa yang menemukan 5 dari 7 tahu bahwa ia belum selesai, dan itu yang mendorong pencarian berlanjut.

---

## Daftar branch

| Branch | Minggu | Jml | Cacat yang harus ada |
|---|:--:|:--:|---|
| w01 | 1 | 4 | Proyek Laravel 12 yang tidak mau jalan sama sekali |
| w02 | 2 | 6 | Satu route saling menutupi · satu method HTTP salah · dua URL hardcode · satu XSS · satu logika query di dalam view |
| w03 | 3 | 7 | Urutan migrasi salah · dua unique composite hilang · satu onDelete keliru · satu $guarded = [] · satu down() kosong · satu controller memakai $request->all() |
| w04 | 4 | 6 | Validasi hanya di frontend · unique pada update yang menolak dirinya sendiri · filter disimpan di session · pagination kehilangan query string · store tanpa redirect · satu form tanpa @csrf |
| w05 | 5 | 7 | Satu route di luar grup auth · dua IDOR (submission dan material) · nested route tanpa scopeBindings · middleware didaftarkan di berkas salah · nama route bentrok antar peran · satu route destruktif memakai GET |
| w06 | 6 | 8 | Model mentah dikembalikan pada dua endpoint · satu endpoint tanpa auth:sanctum · status code salah pada store dan destroy · 403 dikembalikan sebagai 401 · login tanpa throttle · pesan login membocorkan keberadaan email · N+1 pada endpoint daftar |
| w07 | 7 | 9 | @can tanpa Gate::authorize() di dua controller · Policy selalu return true · kebocoran daftar untuk mahasiswa · role bisa diubah lewat update profil · session()->regenerate() hilang · pesan login membocorkan keberadaan email · satu route dosen tanpa middleware · Policy yang menyebabkan N+1 |
| w09 | 9 | 7 | Submission di disk publik · endpoint download tanpa Gate::authorize() · storeAs() memakai nama asli · validasi hanya memeriksa ekstensi · max salah satuan · tidak ada penghapusan berkas fisik · komponen Livewire menyembunyikan tombol tanpa otorisasi server |
| w10 | 10 | 7 | Listener tanpa ShouldQueue · notifikasi dikirim dalam loop (N+1) · payload memuat nilai mahasiswa · endpoint tandai-dibaca tanpa pemeriksaan kepemilikan · tidak ada failed_jobs · job tanpa tries · satu channel broadcast publik |
| w11 | 11 | 6 | N+1 pada tiga tingkat relasi · count() memuat seluruh koleksi · select * padahal dua kolom dipakai · index hilang pada kolom yang sering difilter · seluruh data tanpa pagination · env() dipanggil di luar config/ |
| w12 | 12 | 7 | .env.example memuat kredensial sungguhan · deploy.sh tanpa set -e dan tanpa queue:restart · Nginx mengarah ke akar proyek · APP_DEBUG=true di .env.example · tidak ada Supervisor · db:seed tiap deploy · .gitignore tidak mengabaikan .env |
| w13 | 13 | 6 | .env ter-commit di riwayat · tidak ada branch protection · PR raksasa berisi lima fitur · pesan commit tanpa format · dd() tertinggal di controller · node_modules ter-commit |
| w14 | 14 | 8 | *Test yang menipu:* tiga hanya memeriksa status · dua tanpa assertion bermakna · satu bergantung pada seeder · satu bergantung pada test sebelumnya · satu memeriksa "tidak 200" padahal seharusnya 403 |

Tidak ada branch untuk minggu 8, 15, dan 16: minggu 8 dan 16 adalah asesmen, minggu 15 adalah perapian proyek sendiri.

---

## Cara menyiapkan

*Satu kali di awal semester.* Bangun satu proyek KampusLMS yang sehat dan lengkap sampai minggu 14, lalu turunkan tiap branch dari titik yang sesuai:


main (sehat, lengkap)
 ├─ w01  ← dirusak dari keadaan minggu 1
 ├─ w02  ← dirusak dari keadaan minggu 2
 └─ ...


Tiap branch *hanya memuat kemampuan sampai minggu itu*. Branch w04 yang sudah berisi Policy akan membocorkan materi minggu 7 dan membuat mahasiswa memperbaiki hal yang belum diajarkan.

*Empat aturan penyusunan cacat:*

1. *Cacat harus bergejala, bukan hanya salah secara teori.* Mahasiswa menemukannya dengan menjalankan aplikasi, bukan dengan membaca seluruh kode baris demi baris.
2. *Sebarkan letaknya.* Cacat yang menumpuk di satu berkas ditemukan sekaligus dan tidak melatih apa pun.
3. *Sertakan satu cacat yang gejalanya menyesatkan* di tiap branch — yang tampak seperti masalah A padahal sebabnya B. Ini yang melatih diagnosis, sisanya melatih ketelitian.
4. *Jangan merusak sampai aplikasi tidak bisa dijalankan sama sekali*, kecuali pada w01 yang memang bertema itu. Mahasiswa perlu bisa mengamati gejala.

*Simpan kunci untuk diri sendiri.* Untuk tiap branch, catat di luar repo: berkas dan baris tiap cacat, gejala yang seharusnya muncul, dan cara tercepat menemukannya. Catatan inilah yang Anda pakai saat menilai PR, bukan ingatan.

---

## Cara menilai tahap FIX

Mahasiswa mengirim Pull Request. Yang dinilai bukan jumlah cacat yang ketemu saja:

| Aspek | Yang dilihat |
|---|---|
| Kelengkapan | Berapa dari N cacat ditemukan |
| Ketepatan perbaikan | Apakah diperbaiki pada sebabnya, atau gejalanya ditutup |
| Penjelasan risiko | Deskripsi PR menjelaskan *dampak nyata* tiap cacat, bukan "sudah diperbaiki" |
| Efek samping | Apakah perbaikannya merusak hal lain |

Kolom ketiga adalah pembeda utama. Modul mewajibkannya secara eksplisit sejak w02, dan mahasiswa yang menulis "memperbaiki bug XSS" tanpa menjelaskan apa yang bisa terjadi belum menunjukkan pemahaman yang dinilai.

Perbaikan yang menutup gejala — misalnya menambah try/catch untuk menyembunyikan error N+1 — dinilai sebagai *tidak ditemukan*, meskipun aplikasinya berjalan.

---

## Perawatan antar-semester

| Kapan | Yang perlu dilakukan |
|---|---|
| Laravel naik versi mayor | Jalankan ulang tiap branch; sebagian cacat bisa menjadi tidak relevan atau tertangkap otomatis oleh framework |
| Cacat mulai beredar jawabannya | Ganti letak dan variasinya, pertahankan jenis dan jumlahnya |
| Rata-rata temuan mendekati sempurna | Bukan tanda soal terlalu mudah — periksa dulu apakah jawabannya beredar |
| Ada cacat yang hampir tidak pernah ditemukan | Periksa apakah ia bergejala. Cacat tanpa gejala hanya melatih membaca kode secara acak |