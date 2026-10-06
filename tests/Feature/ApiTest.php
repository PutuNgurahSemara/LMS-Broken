<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'apiuser@test.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'apiuser@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_unauthenticated_api_request_returns_401(): void
    {
        $response = $this->getJson('/api/courses');

        $response->assertStatus(401);
    }

    public function test_authenticated_api_request_returns_course_list(): void
    {
        $user = User::factory()->create();
        Course::factory()->create(['lecturer_id' => $user->id, 'status' => 'active']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/courses');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_api_store_returns_201_created(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($dosen, 'sanctum')->postJson('/api/courses', [
            'code' => 'API101',
            'name' => 'API Course Test',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'API101');
    }

    public function test_api_show_requires_authentication(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id, 'status' => 'active']);

        $response = $this->getJson("/api/courses/{$course->id}");

        $response->assertStatus(401);
    }

    public function test_api_show_and_update_return_resource(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id, 'status' => 'active']);

        // Test Show
        $showResponse = $this->actingAs($dosen, 'sanctum')->getJson("/api/courses/{$course->id}");
        $showResponse->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'code', 'name', 'sks', 'status', 'lecturer']]);

        // Test Update
        $updateResponse = $this->actingAs($dosen, 'sanctum')->putJson("/api/courses/{$course->id}", [
            'code' => $course->code,
            'name' => 'Updated Name',
            'sks' => $course->sks,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);
        $updateResponse->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'code', 'name', 'sks', 'status']]);
    }

    public function test_api_destroy_returns_204_no_content(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id, 'status' => 'active']);

        $response = $this->actingAs($dosen, 'sanctum')->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_authorization_exception_returns_403(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id, 'status' => 'active']);

        // Mahasiswa cannot delete course
        $response = $this->actingAs($mahasiswa, 'sanctum')->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'This action is unauthorized.']);
    }

    public function test_login_throttling_after_5_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'wrong@kampuslms.test',
                'password' => 'wrongpass',
            ])->assertStatus(401);
        }

        // 6th attempt should be throttled
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@kampuslms.test',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(429)
            ->assertJsonStructure(['message']);
    }

    public function test_login_does_not_leak_email_existence(): void
    {
        $user = User::factory()->create([
            'email' => 'registered@kampuslms.test',
            'password' => bcrypt('password123'),
        ]);

        // Wrong password for existing user
        $response1 = $this->postJson('/api/login', [
            'email' => 'registered@kampuslms.test',
            'password' => 'wrongpassword',
        ]);

        // Non-existent user
        $response2 = $this->postJson('/api/login', [
            'email' => 'nonexistent@kampuslms.test',
            'password' => 'wrongpassword',
        ]);

        $response1->assertStatus(401);
        $response2->assertStatus(401);
        $this->assertSame($response1->json('message'), $response2->json('message'));
        $this->assertEquals('Email atau password yang Anda masukkan salah.', $response1->json('message'));
    }
}
