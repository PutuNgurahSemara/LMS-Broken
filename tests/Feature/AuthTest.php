<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@kampuslms.test',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'testuser@kampuslms.test',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@kampuslms.test',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'testuser@kampuslms.test',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_dashboard_page_loads_content(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        // Masalah 4: Tanpa assertion bermakna
        // Kesalahan: Menggunakan assertNotNull($response) yang tidak bermakna karena objek $response selalu tidak null meskipun halaman crash (500) atau kosong
        // Perbaikan: Ganti dengan assertStatus(200) dan periksa keberadaan nama user di konten halaman dashboard
        $response->assertStatus(200);
        $response->assertSee($user->name);
    }
}
