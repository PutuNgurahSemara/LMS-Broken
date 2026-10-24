<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\User;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $notification = $userB->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewAssignmentNotification',
            'data' => [
                'assignment_id' => 1,
                'title' => 'Tugas baru',
                'message' => 'Ada tugas baru',
            ],
        ]);

        $response = $this->actingAs($userA)
            ->post(route('notifications.read', $notification->id));

        $response->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_assignment_notification_does_not_include_student_grades(): void
    {
        $notification = new NewAssignmentNotification(
            Assignment::factory()->create([
                'title' => 'Tugas baru',
                'due_at' => now(),
            ])
        );

        $payload = $notification->toArray(new User);

        $this->assertArrayNotHasKey('student_grades', $payload);
        $this->assertEqualsCanonicalizing([
            'assignment_id',
            'title',
            'course_name',
            'due_at',
            'message',
        ], array_keys($payload));
    }

    public function test_user_can_mark_their_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewAssignmentNotification',
            'data' => [
                'assignment_id' => 1,
                'title' => 'Tugas baru',
                'message' => 'Ada tugas baru',
            ],
        ]);

        $response = $this->actingAs($user)
            ->from(route('notifications.index'))
            ->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('notifications.index'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_view_another_users_notification(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $userB->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewAssignmentNotification',
            'data' => [
                'assignment_id' => 1,
                'title' => 'Tugas privat pengguna lain',
                'message' => 'Pesan privat pengguna lain',
            ],
        ]);

        $response = $this->actingAs($userA)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertDontSee('Pesan privat pengguna lain');
    }
}
