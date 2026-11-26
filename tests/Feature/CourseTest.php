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
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        Course::factory()->create([
            'code' => 'LIST101',
            'name' => 'Kursus Daftar Uji',
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($mahasiswa)->get('/courses');

        $response->assertOk()
            ->assertViewIs('courses.index')
            ->assertSee('LIST101')
            ->assertSee('Kursus Daftar Uji');
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

        $response->assertRedirect('/courses/'.$course->id);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Nama Sesudah Update',
        ]);
    }

    public function test_enrolled_student_can_view_course_detail(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create([
            'code' => 'WEB101',
            'name' => 'Pemrograman Web Uji',
            'lecturer_id' => $dosen->id,
        ]);
        $course->students()->attach($mahasiswa->id);

        $response = $this->actingAs($mahasiswa)->get('/courses/'.$course->id);

        $response->assertOk()
            ->assertViewIs('courses.show')
            ->assertSee('WEB101')
            ->assertSee('Pemrograman Web Uji');
    }

    public function test_mahasiswa_cannot_delete_course(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);

        $response = $this->actingAs($mahasiswa)->delete('/courses/'.$course->id);

        $response->assertForbidden();
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }
}
