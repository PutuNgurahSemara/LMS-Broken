<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_is_stored_on_private_disk_with_a_generated_name(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$student, $assignment] = $this->enrolledStudentAndAssignment();

        $this->actingAs($student)->post(route('submissions.store', $assignment), [
            'file' => UploadedFile::fake()->createWithContent('assignment.pdf', "%PDF-1.4\nsubmission"),
        ])->assertRedirect(route('courses.show', $assignment->course_id));

        $submission = Submission::firstOrFail();

        $this->assertSame('assignment.pdf', $submission->original_name);
        $this->assertStringStartsWith('submissions/', $submission->file_path);
        $this->assertNotSame('submissions/assignment.pdf', $submission->file_path);
        Storage::disk('local')->assertExists($submission->file_path);
        Storage::disk('public')->assertMissing('submissions/assignment.pdf');
    }

    public function test_submission_download_requires_login_and_authorization(): void
    {
        Storage::fake('local');
        [$owner, $assignment] = $this->enrolledStudentAndAssignment();
        $outsider = User::factory()->create(['role' => 'mahasiswa']);
        $path = 'submissions/private.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $owner->id,
            'file_path' => $path,
            'original_name' => 'private.pdf',
            'file_size' => 8,
            'submitted_at' => now(),
        ]);

        $this->get(route('submissions.download', $submission))
            ->assertRedirect(route('login'));

        $this->actingAs($outsider)
            ->get(route('submissions.download', $submission))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('submissions.download', $submission))
            ->assertOk()
            ->assertDownload('private.pdf');
    }

    public function test_upload_rejects_a_file_whose_content_does_not_match_its_extension(): void
    {
        [$student, $assignment] = $this->enrolledStudentAndAssignment();

        $this->actingAs($student)->post(route('submissions.store', $assignment), [
            'file' => UploadedFile::fake()->create('not-a-pdf.pdf', 1, 'text/plain'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_upload_rejects_files_larger_than_ten_megabytes(): void
    {
        [$student, $assignment] = $this->enrolledStudentAndAssignment();

        $this->actingAs($student)->post(route('submissions.store', $assignment), [
            'file' => UploadedFile::fake()->create('large.pdf', 10 * 1024 + 1, 'application/pdf'),
        ])->assertSessionHasErrors('file');
    }

    public function test_resubmitting_replaces_and_removes_the_previous_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$student, $assignment] = $this->enrolledStudentAndAssignment();
        $upload = fn () => UploadedFile::fake()->createWithContent('assignment.pdf', "%PDF-1.4\nsubmission");
        $legacyPath = 'submissions/assignment.pdf';
        Storage::disk('public')->put($legacyPath, '%PDF-1.4 legacy submission');
        Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => $legacyPath,
            'original_name' => 'assignment.pdf',
            'file_size' => 8,
            'submitted_at' => now(),
        ]);
        Storage::disk('public')->assertExists($legacyPath);

        $this->actingAs($student)->post(route('submissions.store', $assignment), ['file' => $upload()]);
        $oldPath = Submission::firstOrFail()->file_path;
        Storage::disk('public')->assertMissing($legacyPath);
        $this->actingAs($student)->post(route('submissions.store', $assignment), ['file' => $upload()]);
        $newPath = Submission::firstOrFail()->file_path;

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newPath);
    }

    public function test_deleting_assignment_removes_submission_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$student, $assignment] = $this->enrolledStudentAndAssignment();
        $path = 'submissions/to-delete.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');
        Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => $path,
            'original_name' => 'to-delete.pdf',
            'file_size' => 8,
            'submitted_at' => now(),
        ]);

        $assignment->delete();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_course_removes_files_from_its_assignments(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$student, $assignment] = $this->enrolledStudentAndAssignment();
        $path = 'submissions/course-file.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');
        Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => $path,
            'original_name' => 'course-file.pdf',
            'file_size' => 8,
            'submitted_at' => now(),
        ]);

        $assignment->course->delete();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_student_removes_their_submission_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$student, $assignment] = $this->enrolledStudentAndAssignment();
        $path = 'submissions/student-file.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');
        Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => $path,
            'original_name' => 'student-file.pdf',
            'file_size' => 8,
            'submitted_at' => now(),
        ]);

        $student->delete();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_grading_endpoint_enforces_server_side_authorization(): void
    {
        [$student, $assignment] = $this->enrolledStudentAndAssignment();
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'original_name' => 'assignment.pdf',
            'file_size' => 8,
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->post(route('submissions.grade', $submission), ['score' => 90])
            ->assertForbidden();

        $this->assertDatabaseMissing('grades', ['submission_id' => $submission->id]);
    }

    /**
     * @return array{User, Assignment}
     */
    private function enrolledStudentAndAssignment(): array
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student->id);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        return [$student, $assignment];
    }
}
