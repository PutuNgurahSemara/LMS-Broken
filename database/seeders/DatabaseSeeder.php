<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Mandatory accounts
        $admin = User::forceCreate([
            'name' => 'Administrator Kampus',
            'email' => 'admin@kampuslms.test',
            'password' => Hash::make('password'),
            'nim_nip' => 'ADM001',
            'role' => 'admin',
        ]);

        $dosenMain = User::forceCreate([
            'name' => 'Dr. Aris Sugiharto',
            'email' => 'dosen@kampuslms.test',
            'password' => Hash::make('password'),
            'nim_nip' => '198001012005011001',
            'role' => 'dosen',
        ]);

        $dosen2 = User::forceCreate([
            'name' => 'Budi Santoso, M.T.',
            'email' => 'budi.santoso@kampuslms.test',
            'password' => Hash::make('password'),
            'nim_nip' => '198502022010011002',
            'role' => 'dosen',
        ]);

        $dosen3 = User::forceCreate([
            'name' => 'Siti Aminah, Ph.D.',
            'email' => 'siti.aminah@kampuslms.test',
            'password' => Hash::make('password'),
            'nim_nip' => '199003032015012003',
            'role' => 'dosen',
        ]);

        $mahasiswaMain = User::forceCreate([
            'name' => 'Budi Pratama',
            'email' => 'mahasiswa@kampuslms.test',
            'password' => Hash::make('password'),
            'nim_nip' => '24060121120001',
            'role' => 'mahasiswa',
        ]);

        $dosens = collect([$dosenMain, $dosen2, $dosen3]);
        $mahasiswas = collect([$mahasiswaMain]);

        for ($i = 2; $i <= 30; $i++) {
            $mhs = User::forceCreate([
                'name' => "Mahasiswa {$i}",
                'email' => "mahasiswa{$i}@kampuslms.test",
                'password' => Hash::make('password'),
                'nim_nip' => '240601211200'.str_pad($i, 2, '0', STR_PAD_LEFT),
                'role' => 'mahasiswa',
            ]);
            $mahasiswas->push($mhs);
        }

        // 2. Courses
        $coursesData = [
            ['code' => 'SI2514024', 'name' => 'Pemrograman Web', 'sks' => 3, 'lecturer_id' => $dosenMain->id, 'description' => 'Mata kuliah dasar pengembangan aplikasi web modern menggunakan Laravel 12.'],
            ['code' => 'SI2514025', 'name' => 'Basis Data Lanjut', 'sks' => 3, 'lecturer_id' => $dosen2->id, 'description' => 'Pembahasan indexing, transaksi, dan optimisasi query database relational.'],
            ['code' => 'SI2514026', 'name' => 'Keamanan Informasi', 'sks' => 2, 'lecturer_id' => $dosen3->id, 'description' => 'Konsep dasar enkripsi, OWASP Top 10, dan pencegahan XSS/CSRF.'],
            ['code' => 'SI2514027', 'name' => 'Pemrograman Berorientasi Objek', 'sks' => 3, 'lecturer_id' => $dosenMain->id, 'description' => 'Prinsip OOP: Encapsulation, Inheritance, Polymorphism, Abstraction.'],
            ['code' => 'SI2514028', 'name' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'lecturer_id' => $dosen2->id, 'description' => 'Metodologi Agile, Scrum, dan Siklus Hidup Perangkat Lunak.'],
        ];

        foreach ($coursesData as $cData) {
            $course = Course::create(array_merge($cData, ['status' => 'active']));

            // Enroll all students into course
            foreach ($mahasiswas as $mhs) {
                $course->students()->attach($mhs->id, ['enrolled_at' => now()]);
            }

            // Add Materials
            Material::create([
                'course_id' => $course->id,
                'uploaded_by' => $course->lecturer_id,
                'title' => 'Pengenalan '.$course->name,
                'description' => 'Slide presentasi pengantar mata kuliah.',
                'type' => 'link',
                'external_url' => 'https://kampuslms.test/materials/intro.pdf',
            ]);

            // Add Assignments
            $assignment = Assignment::create([
                'course_id' => $course->id,
                'title' => 'Tugas 1 '.$course->name,
                'description' => 'Kerjakan modul praktikum 1.',
                'due_at' => now()->addDays(7),
                'max_score' => 100,
            ]);

            // Add Submissions for all students
            foreach ($mahasiswas as $mhs) {
                $sub = Submission::create([
                    'assignment_id' => $assignment->id,
                    'user_id' => $mhs->id,
                    'file_path' => 'submissions/sample.pdf',
                    'original_name' => 'Tugas_1_'.$mhs->nim_nip.'.pdf',
                    'file_size' => 1024500,
                    'notes' => 'Berikut berkas tugas saya.',
                    'submitted_at' => now(),
                    'status' => 'graded',
                ]);

                Grade::create([
                    'submission_id' => $sub->id,
                    'graded_by' => $course->lecturer_id,
                    'score' => rand(75, 100),
                    'feedback' => 'Kerja bagus! Pertahankan.',
                    'graded_at' => now(),
                ]);
            }
        }
    }
}
