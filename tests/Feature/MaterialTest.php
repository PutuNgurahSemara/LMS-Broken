<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_delete_authorization_cannot_delete_material(): void
    {
        Storage::fake('local');
        [$lecturer, $student, $material] = $this->materialWithCourse();
        Storage::disk('local')->put($material->file_path, 'material');

        $this->actingAs($student)
            ->delete(route('materials.destroy', $material))
            ->assertForbidden();

        $this->assertDatabaseHas('materials', ['id' => $material->id]);
        Storage::disk('local')->assertExists($material->file_path);
    }

    public function test_authorized_user_deleting_material_removes_record_and_file(): void
    {
        Storage::fake('local');
        [$lecturer, , $material] = $this->materialWithCourse();
        Storage::disk('local')->put($material->file_path, 'material');

        $this->actingAs($lecturer)
            ->delete(route('materials.destroy', $material))
            ->assertRedirect(route('courses.show', $material->course_id));

        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        Storage::disk('local')->assertMissing($material->file_path);
    }

    /**
     * @return array{User, User, Material}
     */
    private function materialWithCourse(): array
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Course material',
            'type' => 'file',
            'file_path' => 'materials/course-material.pdf',
            'original_name' => 'course-material.pdf',
            'file_size' => 8,
            'mime_type' => 'application/pdf',
        ]);

        return [$lecturer, $student, $material];
    }
}
