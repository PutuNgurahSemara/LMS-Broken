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

class Week09Test extends TestCase
{
    use RefreshDatabase;

    protected User $dosen;
    protected User $mahasiswa1;
    protected User $mahasiswa2;
    protected Course $course;
    protected Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->dosen = User::factory()->create([
            'role' => 'dosen',
            'email' => 'dosen@kampuslms.test',
        ]);

        $this->mahasiswa1 = User::factory()->create([
            'role' => 'mahasiswa',
            'email' => 'mhs1@kampuslms.test',
        ]);

        $this->mahasiswa2 = User::factory()->create([
            'role' => 'mahasiswa',
            'email' => 'mhs2@kampuslms.test',
        ]);

        $this->course = Course::factory()->create([
            'lecturer_id' => $this->dosen->id,
            'status' => 'active',
        ]);

        $this->course->students()->attach([$this->mahasiswa1->id, $this->mahasiswa2->id]);

        $this->assignment = Assignment::factory()->create([
            'course_id' => $this->course->id,
            'due_at' => now()->addDays(7),
        ]);
    }

    /**
     * Error 1: max:10 (salah satuan KB vs MB)
     */
    public function test_submission_accepts_file_larger_than_10kb(): void
    {
        $file = UploadedFile::fake()->create('laporan.pdf', 500, 'application/pdf'); // 500 KB

        $response = $this->actingAs($this->mahasiswa1)
            ->post(route('submissions.store', $this->assignment), [
                'file' => $file,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->mahasiswa1->id,
        ]);
    }

    /**
     * Error 2: Validasi hanya ekstensi (extensions) bukan mimes
     */
    public function test_submission_rejects_php_file_disguised_as_pdf(): void
    {
        // Fake file with .pdf extension but text/x-php mime type (size 5KB, so it passes max:10KB)
        $file = UploadedFile::fake()->create('shell.pdf', 5, 'text/x-php');

        $response = $this->actingAs($this->mahasiswa1)
            ->post(route('submissions.store', $this->assignment), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }

    /**
     * Error 3: storeAs memakai nama asli ($originalName) alih-alih hash acak
     */
    public function test_submission_files_are_stored_with_random_hashed_name(): void
    {
        $file1 = UploadedFile::fake()->create('tugas.pdf', 5, 'application/pdf');
        $this->actingAs($this->mahasiswa1)->post(route('submissions.store', $this->assignment), ['file' => $file1]);

        $file2 = UploadedFile::fake()->create('tugas.pdf', 5, 'application/pdf');
        $this->actingAs($this->mahasiswa2)->post(route('submissions.store', $this->assignment), ['file' => $file2]);

        $sub1 = Submission::where('user_id', $this->mahasiswa1->id)->first();
        $sub2 = Submission::where('user_id', $this->mahasiswa2->id)->first();

        $this->assertNotNull($sub1);
        $this->assertNotNull($sub2);
        $this->assertNotEquals($sub1->file_path, $sub2->file_path, 'File paths must be unique to prevent overwriting');
        $this->assertNotEquals('submissions/tugas.pdf', $sub1->file_path);
    }

    /**
     * Error 4: submission disimpan di disk publik alih-alih privat (local)
     */
    public function test_submission_is_stored_on_local_private_disk_not_public(): void
    {
        $file = UploadedFile::fake()->create('tugas.pdf', 5, 'application/pdf');

        $this->actingAs($this->mahasiswa1)->post(route('submissions.store', $this->assignment), [
            'file' => $file,
        ]);

        $sub = Submission::where('user_id', $this->mahasiswa1->id)->first();
        $this->assertNotNull($sub);

        Storage::disk('local')->assertExists($sub->file_path);
        Storage::disk('public')->assertMissing($sub->file_path);
    }

    /**
     * Error 5: endpoint download tanpa Gate::authorize() (IDOR) & disk publik
     */
    public function test_submission_download_is_protected_by_policy_against_idor(): void
    {
        $file = UploadedFile::fake()->create('tugas.pdf', 5, 'application/pdf');
        $this->actingAs($this->mahasiswa1)->post(route('submissions.store', $this->assignment), [
            'file' => $file,
        ]);

        $sub = Submission::where('user_id', $this->mahasiswa1->id)->first();

        // Mahasiswa 2 attempts to download Mahasiswa 1's submission -> MUST be 403 Forbidden
        $response = $this->actingAs($this->mahasiswa2)->get(route('submissions.download', $sub));
        $response->assertForbidden();

        // Owner (Mahasiswa 1) CAN download
        $ownerResponse = $this->actingAs($this->mahasiswa1)->get(route('submissions.download', $sub));
        $ownerResponse->assertOk();

        // Lecturer CAN download
        $lecturerResponse = $this->actingAs($this->dosen)->get(route('submissions.download', $sub));
        $lecturerResponse->assertOk();
    }

    /**
     * Error 6: berkas fisik materi tidak dihapus saat record materi dihapus
     */
    public function test_material_physical_file_is_deleted_when_material_destroyed(): void
    {
        $file = UploadedFile::fake()->create('materi.pdf', 100, 'application/pdf');
        $filePath = $file->storeAs('materials', 'hashed_material.pdf', 'local');

        $material = Material::create([
            'course_id' => $this->course->id,
            'uploaded_by' => $this->dosen->id,
            'title' => 'Materi Pertemuan 1',
            'type' => 'file',
            'file_path' => $filePath,
            'original_name' => 'materi.pdf',
            'file_size' => 100 * 1024,
            'mime_type' => 'application/pdf',
        ]);

        Storage::disk('local')->assertExists($filePath);

        $this->actingAs($this->dosen)->delete(route('materials.destroy', $material));

        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        Storage::disk('local')->assertMissing($filePath);
    }

    /**
     * Error 7: otorisasi Gate::authorize('delete', $material) dihilangkan
     */
    public function test_material_destruction_requires_authorization(): void
    {
        $material = Material::create([
            'course_id' => $this->course->id,
            'uploaded_by' => $this->dosen->id,
            'title' => 'Materi Rahasia',
            'type' => 'link',
            'external_url' => 'https://example.com',
        ]);

        // Unauthorized student tries to delete lecturer material -> MUST be 403 Forbidden
        $response = $this->actingAs($this->mahasiswa1)->delete(route('materials.destroy', $material));
        $response->assertForbidden();

        $this->assertDatabaseHas('materials', ['id' => $material->id]);
    }
}
