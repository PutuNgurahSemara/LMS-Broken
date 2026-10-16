<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndWeek09Test extends TestCase
{
    use RefreshDatabase;

    public function test_complete_end_to_end_week09_lifecycle(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        // 1. Setup Demo Users
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@kampuslms.test']);
        $dosen = User::factory()->create(['role' => 'dosen', 'email' => 'dosen@kampuslms.test']);
        $mhs1 = User::factory()->create(['role' => 'mahasiswa', 'email' => 'mahasiswa@kampuslms.test']);
        $mhs2 = User::factory()->create(['role' => 'mahasiswa', 'email' => 'mahasiswa2@kampuslms.test']);
        $outsider = User::factory()->create(['role' => 'mahasiswa', 'email' => 'outsider@kampuslms.test']);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
            'name' => 'Pemrograman Web',
            'status' => 'active',
        ]);
        $course->students()->attach([$mhs1->id, $mhs2->id]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Tugas 1: Upload File',
            'due_at' => now()->addDays(3),
        ]);

        // 2. Dosen unggah materi
        $materialFile = UploadedFile::fake()->create('slide-minggu-9.pdf', 2048, 'application/pdf');
        $uploadMatResponse = $this->actingAs($dosen)->post(route('materials.store', $course), [
            'title' => 'Slide Minggu 09',
            'type' => 'file',
            'file' => $materialFile,
        ]);
        $uploadMatResponse->assertRedirect(route('courses.show', $course));

        $material = Material::where('title', 'Slide Minggu 09')->first();
        $this->assertNotNull($material);
        $this->assertEquals('file', $material->type);
        $this->assertEquals('slide-minggu-9.pdf', $material->original_name);
        Storage::disk('local')->assertExists($material->file_path);

        // 3. Mahasiswa terdaftar mengunduh materi
        $downloadMatMhs = $this->actingAs($mhs1)->get(route('materials.download', $material));
        $downloadMatMhs->assertOk();

        // 4. Mahasiswa tidak terdaftar ditolak mengunduh materi (403)
        $downloadMatOutsider = $this->actingAs($outsider)->get(route('materials.download', $material));
        $downloadMatOutsider->assertForbidden();

        // 5. Mahasiswa 1 kumpul tugas
        $subFile = UploadedFile::fake()->create('jawaban-tugas.pdf', 1024, 'application/pdf');
        $submitResponse = $this->actingAs($mhs1)->post(route('submissions.store', $assignment), [
            'file' => $subFile,
            'notes' => 'Berikut tugas saya Pak.',
        ]);
        $submitResponse->assertRedirect(route('courses.show', $course->id));

        $submission = Submission::where('user_id', $mhs1->id)->where('assignment_id', $assignment->id)->first();
        $this->assertNotNull($submission);
        $this->assertEquals('jawaban-tugas.pdf', $submission->original_name);
        Storage::disk('local')->assertExists($submission->file_path);
        Storage::disk('public')->assertMissing($submission->file_path);

        // 6. Dosen melihat daftar pengumpulan
        $dosenViewAssigment = $this->actingAs($dosen)->get(route('assignments.show', $assignment));
        $dosenViewAssigment->assertOk();
        $dosenViewAssigment->assertSee($mhs1->name);
        $dosenViewAssigment->assertSee('jawaban-tugas.pdf');

        // 7. Unduh submission oleh pemilik, dosen pengampu, admin
        $this->actingAs($mhs1)->get(route('submissions.download', $submission))->assertOk();
        $this->actingAs($dosen)->get(route('submissions.download', $submission))->assertOk();
        $this->actingAs($admin)->get(route('submissions.download', $submission))->assertOk();

        // 8. Unduh submission oleh mahasiswa lain ditolak (403)
        $this->actingAs($mhs2)->get(route('submissions.download', $submission))->assertForbidden();

        // 9. Dosen menghapus materi: berkas fisik harus terhapus dari disk local
        $matFilePath = $material->file_path;
        $deleteMatResponse = $this->actingAs($dosen)->delete(route('materials.destroy', $material));
        $deleteMatResponse->assertRedirect(route('courses.show', $course));
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        Storage::disk('local')->assertMissing($matFilePath);
    }
}
