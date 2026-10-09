<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrokenWeek6Test extends TestCase
{
    use RefreshDatabase;

    // 1. CourseController@store returns 201
    public function test_error_1_store_returns_201_created()
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $response = $this->actingAs($dosen, 'sanctum')->postJson('/api/courses', [
            'code' => 'API201',
            'name' => 'Test Course 201',
            'sks' => 3,
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);
        $response->assertStatus(201);
    }

    // 2. api.php show route protected by sanctum
    public function test_error_2_show_route_protected()
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        
        $response = $this->getJson('/api/courses/' . $course->id);
        $response->assertStatus(401);
    }

    // 3. bootstrap/app.php AuthorizationException returns 403 instead of 401
    public function test_error_3_authorization_returns_403()
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $otherDosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $otherDosen->id]);
        
        // Dosen trying to view another dosen's course should be 403 (unauthorized/forbidden)
        $response = $this->actingAs($dosen, 'sanctum')->getJson('/api/courses/' . $course->id);
        $response->assertStatus(403);
    }

    // 4. Rate limiting configured
    public function test_error_4_rate_limiting_applied()
    {
        $response = $this->postJson('/api/login');
        $response->assertHeader('X-RateLimit-Limit');
    }

    // 5. JSON response without Accept header for api/*
    public function test_error_5_api_returns_json_without_accept_header()
    {
        $response = $this->get('/api/courses'); // Note: get(), not getJson()
        // It should return 401 Unauthenticated in JSON, not redirect
        $response->assertStatus(401)
                 ->assertHeader('Content-Type', 'application/json');
    }

    // 6. destroy returns 204
    public function test_error_6_destroy_returns_204()
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        
        $response = $this->actingAs($dosen, 'sanctum')->deleteJson('/api/courses/' . $course->id);
        $response->assertStatus(204);
    }

    // 7. show and update return resource wrapper
    public function test_error_7_show_returns_resource()
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $dosen->id]);
        
        $response = $this->actingAs($dosen, 'sanctum')->getJson('/api/courses/' . $course->id);
        $response->assertJsonStructure(['data' => ['id', 'code', 'name']]);
    }

    // 8. index eager loads lecturer
    public function test_error_8_index_eager_loads()
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        Course::factory()->create(['lecturer_id' => $dosen->id, 'status' => 'active']);
        
        $response = $this->actingAs($dosen, 'sanctum')->getJson('/api/courses');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'lecturer' => ['id', 'name', 'email']
                ]
            ]
        ]);
    }
}
