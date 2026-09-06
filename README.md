# LMS Rusak

**Pemrograman Web | SI2514024 | Ganjil 2026/2027**

---

Tahap **FIX** setiap minggu memakai proyek Laravel yang sengaja dirusak. Semuanya berada di **satu repositori**, dipisahkan per branch.

```bash
git clone https://github.com/PutuNgurahSemara/LMS-Broken.git
cd kampuslms-broken
git fetch origin
```
```bash 
git switch w02
git pull origin w02
```
 switch sesuaikan dengan minggu berapa yang mau dikerjakan


## Branch per minggu

| Minggu | Branch | Masalah | Minggu | Branch | Masalah |
|---:|---|---:|---:|---|---:|
| 1 | `w01` | 4 | 9 | `w09` | 7 |
| 2 | `w02` | 6 | 10 | `w10` | 7 |
| 3 | `w03` | 7 | 11 | `w11` | 6 |
| 4 | `w04` | 6 | 12 | `w12` | 7 |
| 5 | `w05` | 7 | 13 | `w13` | 6 |
| 6 | `w06` | 8 | 14 | `w14` | 8 |
| 7 | `w07` | 9 | | | |

Minggu 8, 15, dan 16 tidak punya branch cacat.

Jumlah masalah selalu disebutkan supaya Anda tahu kapan berhenti mencari. Kalau baru ketemu 4 dari 7, memang masih ada.

## Cara mengumpulkan

Fork ke akun kelompok, perbaiki di branch Anda sendiri, lalu kirim **Pull Request ke branch minggu tersebut**. PR harus menjelaskan tiap perbaikan: apa yang salah, kenapa berbahaya, dan bagaimana Anda membuktikannya sudah beres.

PR tanpa penjelasan tidak dinilai, meskipun kodenya benar.

## Cara lengkapnya 
Buka repositori hasil fork di GitHub (https://github.com/NAMA-KELOMPOK/LMS-Broken).
Klik tombol hijau "Compare & pull request".

## ⚠️ PENTING (Pengaturan Target Branch):

base repository: PutuNgurahSemara/LMS-Broken (Repo Asdos)

base branch: w02 (harus sama dengan branch minggu tersebut, JANGAN ke main)

head repository: NAMA-KELOMPOK/LMS-Broken (Repo Kelompok)

compare branch: w02

Isi Deskripsi PR: Mahasiswa wajib menuliskan deskripsi PR sesuai aturan modul:

- Masalah yang ditemukan: Apa yang salah dan di berkas mana.
- Risiko Keamanan/Dampak: Mengapa masalah itu berbahaya.
- Bukti Pembetulan: Bagaimana mereka membuktikannya sudah beres.
