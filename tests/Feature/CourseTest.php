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

    public function test_invalid_course_data_is_rejected_by_store(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($dosen)->post('/courses', [
            'code' => 'INVALID101',
            'name' => '',
            'sks' => 7,
            'lecturer_id' => 999999,
            'status' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['name', 'sks', 'lecturer_id', 'status']);
        $this->assertDatabaseMissing('courses', ['code' => 'INVALID101']);
    }

    public function test_course_can_be_updated_with_its_existing_code(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id, 'code' => 'OWN101']);

        $response = $this->actingAs($dosen)->put('/courses/'.$course->id, [
            'code' => 'OWN101',
            'name' => 'Updated Course',
            'sks' => 4,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect('/courses/'.$course->id);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'OWN101',
            'name' => 'Updated Course',
        ]);
    }

    public function test_course_update_rejects_another_courses_code(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id, 'code' => 'OWN102']);
        $otherCourse = Course::factory()->create(['code' => 'OTHER102']);

        $response = $this->actingAs($dosen)->put('/courses/'.$course->id, [
            'code' => $otherCourse->code,
            'name' => $course->name,
            'sks' => $course->sks,
            'lecturer_id' => $dosen->id,
            'status' => $course->status,
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'OWN102',
        ]);
    }

    public function test_course_filters_are_not_saved_between_requests(): void
    {
        $user = User::factory()->create();
        $activeCourse = Course::factory()->create(['name' => 'Active Course', 'status' => 'active']);
        $draftCourse = Course::factory()->create(['name' => 'Draft Course', 'status' => 'draft']);

        $this->actingAs($user)->get('/courses?status=draft')->assertOk();
        $response = $this->actingAs($user)->get('/courses');

        $response->assertViewHas('courses', function ($courses) use ($activeCourse, $draftCourse) {
            return $courses->contains('id', $activeCourse->id)
                && ! $courses->contains('id', $draftCourse->id);
        });
    }

    public function test_course_pagination_preserves_filter_query_string(): void
    {
        $user = User::factory()->create();
        Course::factory()->count(11)->create(['name' => 'Target Course', 'status' => 'active']);

        $response = $this->actingAs($user)->get('/courses?search=Target&status=active&page=2');

        $response->assertViewHas('courses', function ($courses) {
            $paginationUrl = $courses->url(2);

            return str_contains($paginationUrl, 'search=Target')
                && str_contains($paginationUrl, 'status=active');
        });
    }

    public function test_course_store_without_csrf_token_is_rejected(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($dosen)->post('/courses', [
            'code' => 'NO-CSRF101',
            'name' => 'No CSRF Course',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        $response->assertStatus(419);
        $this->assertDatabaseMissing('courses', ['code' => 'NO-CSRF101']);
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
}
