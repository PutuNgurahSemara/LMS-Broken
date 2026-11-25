<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_courses_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/courses');

        $response->assertStatus(200);
    }

    public function test_dosen_can_create_a_course(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($dosen)->post('/courses', [
            'code' => 'TEST101',
            'name' => 'Testing Course',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
            'description' => 'Test description',
        ]);

        $response->assertRedirect('/courses');
        $this->assertDatabaseHas('courses', [
            'code' => 'TEST101',
            'name' => 'Testing Course',
        ]);
    }

    public function test_mahasiswa_cannot_create_a_course(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($mahasiswa)->post('/courses', [
            'code' => 'HACK101',
            'name' => 'Hacked Course',
            'sks' => 3,
            'lecturer_id' => $mahasiswa->id,
            'status' => 'active',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('courses', ['code' => 'HACK101']);
    }

    public function test_dosen_can_update_course(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
            'name' => 'Nama Sebelum Update',
        ]);

        $response = $this->actingAs($dosen)->put('/courses/'.$course->id, [
            'code' => $course->code,
            'name' => 'Nama Sesudah Update',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        // Masalah 3: Hanya memeriksa status 302 (tanpa memeriksa pembaruan database)
        // Kesalahan: Hanya memeriksa assertStatus(302). Jika controller me-redirect tanpa mengupdate record di database, test tetap hijau
        // Perbaikan: Tambahkan assertRedirect dan assertDatabaseHas untuk memastikan nama mata kuliah telah terupdate di database
        $response->assertRedirect('/courses/'.$course->id);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Nama Sesudah Update',
        ]);
    }

    public function test_default_pemrograman_web_course_can_be_viewed(): void
    {
        // Masalah 6: Test bergantung pada seeder
        // Kesalahan: Mengambil data dari seeder (User::where('email', ...) dan Course::where('code', ...)) yang menyebabkan test gagal saat database kosong di RefreshDatabase
        // Perbaikan: Buat data uji sendiri secara mandiri menggunakan factory dan daftarkan mahasiswa ke mata kuliah
        $dosen = User::factory()->create(['role' => 'dosen']);
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($user->id);

        $response = $this->actingAs($user)->get('/courses/'.$course->id);

        $response->assertStatus(200);
        $response->assertSee($course->name);
    }

    public function test_mahasiswa_cannot_delete_course(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $response = $this->actingAs($mahasiswa)->delete('/courses/'.$course->id);

        // Masalah 8: Memeriksa "tidak 200" padahal seharusnya 403
        // Kesalahan: Menggunakan assertNotEquals(200) yang longgar, sehingga jika endpoint melempar error 500 crash atau 404 test tetap hijau palsu
        // Perbaikan: Gunakan assertStatus(403) untuk memastikan otorisasi berjalan dan assertDatabaseHas agar mata kuliah dipastikan tidak terhapus
        $response->assertStatus(403);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }
}
