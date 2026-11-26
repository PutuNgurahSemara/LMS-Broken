<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_dosen_role(): void
    {
        $user = User::factory()->create(['role' => 'dosen']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'dosen',
        ]);
    }
}
