<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class Week7PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_owner_lecturer_cannot_update_or_delete_a_course(): void
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $owner->id]);

        $update = $this->actingAs($otherLecturer)->put(route('courses.update', $course), [
            'code' => 'NEW101',
            'name' => 'Updated course',
            'sks' => 3,
            'lecturer_id' => $owner->id,
            'status' => 'active',
        ]);

        $update->assertForbidden();
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'code' => $course->code]);

        $this->actingAs($otherLecturer)
            ->delete(route('courses.destroy', $course))
            ->assertForbidden();
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_non_owner_lecturer_cannot_delete_course_material(): void
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $owner->id]);
        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $owner->id,
            'title' => 'Lecture notes',
            'type' => 'link',
            'external_url' => 'https://kampuslms.test/notes',
        ]);

        $this->actingAs($otherLecturer)
            ->delete(route('materials.destroy', $material))
            ->assertForbidden();

        $this->assertDatabaseHas('materials', ['id' => $material->id]);
    }

    public function test_submission_policy_does_not_allow_an_unrelated_lecturer_to_grade(): void
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $owner->id]);
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'title' => 'Assignment',
            'due_at' => now()->addDay(),
            'max_score' => 100,
        ]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($otherLecturer)
            ->post(route('submissions.grade', $submission), ['score' => 80])
            ->assertForbidden();

        $this->assertDatabaseMissing('grades', ['submission_id' => $submission->id]);
    }

    public function test_mahasiswa_cannot_view_user_directory(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_user_cannot_change_own_role(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)
            ->put(route('users.update', $student), [
                'name' => $student->name,
                'email' => $student->email,
                'nim_nip' => $student->nim_nip,
                'role' => 'admin',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'role' => 'mahasiswa']);
    }

    public function test_successful_login_regenerates_the_session_id(): void
    {
        $loginPage = $this->get(route('login'));
        $sessionCookie = $loginPage->getCookie(config('session.cookie'))->getValue();

        Auth::shouldReceive('attempt')->once()->andReturnTrue();
        RateLimiter::shouldReceive('tooManyAttempts')->once()->andReturnFalse();
        RateLimiter::shouldReceive('clear')->once();

        $response = $this->withoutMiddleware(RedirectIfAuthenticated::class)
            ->withCookie(config('session.cookie'), $sessionCookie)
            ->post(route('login'), [
                'email' => 'user@kampuslms.test',
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertNotSame(
            $sessionCookie,
            $response->getCookie(config('session.cookie'))->getValue()
        );
    }

    public function test_login_error_does_not_disclose_whether_the_email_exists(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $unknownEmail = $this->from(route('login'))->post(route('login'), [
            'email' => 'unknown@kampuslms.test',
            'password' => 'wrong-password',
        ]);
        $unknownEmailMessage = $unknownEmail->getSession()->get('errors')->getBag('default')->first('email');

        $knownEmail = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        $knownEmailMessage = $knownEmail->getSession()->get('errors')->getBag('default')->first('email');

        $unknownEmail->assertSessionHasErrors('email');
        $knownEmail->assertSessionHasErrors('email');
        $this->assertSame($unknownEmailMessage, $knownEmailMessage);
    }

    public function test_course_creation_page_requires_authentication(): void
    {
        $this->get(route('courses.create'))->assertRedirect(route('login'));
    }

    public function test_course_view_policy_checks_enrollment_with_an_exists_query(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            if (str_contains(strtolower($query->sql), 'course_user')) {
                $queries[] = strtolower($query->sql);
            }
        });

        $this->assertFalse(Gate::forUser($student)->allows('view', $course));
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('exists', $queries[0]);
    }
}
