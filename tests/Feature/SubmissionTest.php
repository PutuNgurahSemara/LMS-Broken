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

    public function test_mahasiswa_can_submit_assignment(): void
    {
        Storage::fake('local');

        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $course->students()->attach($mahasiswa->id);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $file = UploadedFile::fake()->create('tugas.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($mahasiswa)->post('/assignments/'.$assignment->id.'/submissions', [
            'file' => $file,
            'notes' => 'Catatan pengumpulan tugas',
        ]);

        $response->assertRedirect('/courses/'.$course->id);
        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
            'original_name' => 'tugas.pdf',
        ]);
    }

    public function test_dosen_can_grade_submission(): void
    {
        // Masalah 7: Test bergantung pada test sebelumnya (Test Dependency via static state)
        // Kesalahan: Menggunakan Submission::find(self::$createdSubmissionId) yang bergantung pada eksekusi test sebelumnya. Karena RefreshDatabase mereset database tiap test, id tersebut bernilai null dan test gagal
        // Perbaikan: Hapus static property $createdSubmissionId, buat data dosen, course, assignment, dan submission sendiri secara mandiri di dalam test
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $mahasiswa->id,
            'file_path' => 'submissions/sample.pdf',
            'original_name' => 'tugas.pdf',
            'file_size' => 1024,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($dosen)->post('/submissions/'.$submission->id.'/grade', [
            'score' => 85,
            'feedback' => 'Bagus sekali',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'score' => 85,
            'feedback' => 'Bagus sekali',
        ]);
    }
}
