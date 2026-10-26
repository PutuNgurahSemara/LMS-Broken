<?php

namespace Tests\Feature;

use App\Events\AssignmentCreated;
use App\Jobs\SendAssignmentNotificationJob;
use App\Listeners\SendAssignmentNotificationListener;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class Week10FixTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_listener_uses_queue_and_dispatches_delivery_job(): void
    {
        $assignment = Assignment::factory()->create();

        $this->assertTrue(
            is_subclass_of(
                SendAssignmentNotificationListener::class,
                ShouldQueue::class
            )
        );

        Queue::fake();

        $listener = new SendAssignmentNotificationListener();
        $listener->handle(new AssignmentCreated($assignment));

        Queue::assertPushed(
            SendAssignmentNotificationJob::class,
            fn ($job) => $job->assignmentId === $assignment->id
        );
    }

    public function test_job_notifies_only_enrolled_students_with_safe_payload(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Tugas Week 10',
        ]);

        $studentA = User::factory()->create(['role' => 'mahasiswa']);
        $studentB = User::factory()->create(['role' => 'mahasiswa']);
        $outsider = User::factory()->create(['role' => 'mahasiswa']);

        $course->students()->attach(
            [$studentA->id, $studentB->id],
            ['enrolled_at' => now()]
        );

        (new SendAssignmentNotificationJob(
            (int) $assignment->id
        ))->handle();

        $this->assertSame(1, $studentA->notifications()->count());
        $this->assertSame(1, $studentB->notifications()->count());
        $this->assertSame(0, $outsider->notifications()->count());

        $notification = $studentA->notifications()->firstOrFail();

        $this->assertSame([
            'assignment_id' => $assignment->id,
            'title' => 'Tugas Week 10',
            'course_name' => $course->name,
            'due_at' => $assignment->due_at->toIso8601String(),
            'message' => "Tugas baru 'Tugas Week 10' telah ditambahkan.",
        ], $notification->data);

        $this->assertArrayNotHasKey('student_grades', $notification->data);
        $this->assertArrayNotHasKey('score', $notification->data);
        $this->assertArrayNotHasKey('feedback', $notification->data);
    }

    public function test_users_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $notification = $owner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test-notification',
            'data' => [
                'message' => 'Notifikasi privat',
            ],
        ]);

        $this->actingAs($otherUser)
            ->post("/notifications/{$notification->id}/read")
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($owner)
            ->from('/notifications')
            ->post("/notifications/{$notification->id}/read")
            ->assertRedirect('/notifications');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_queue_retry_failure_logging_and_failed_jobs_table_are_configured(): void
    {
        $job = new SendAssignmentNotificationJob(123);

        $this->assertSame(3, $job->tries);
        $this->assertSame(10, $job->backoff);
        $this->assertTrue(Schema::hasTable('failed_jobs'));

        Log::spy();

        $job->failed(new \RuntimeException('Simulasi kegagalan'));

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'Pengiriman notifikasi tugas gagal setelah percobaan ulang.',
                [
                    'assignment_id' => 123,
                    'exception_class' => \RuntimeException::class,
                ]
            );
    }

    public function test_job_is_recorded_in_failed_jobs_after_three_failed_attempts(): void
    {
        config(['queue.default' => 'database']);

        $assignment = Assignment::factory()->create();

        $student = User::factory()->create(['role' => 'mahasiswa']);

        $assignment->course->students()->attach(
            $student->id,
            ['enrolled_at' => now()]
        );

        Notification::shouldReceive('send')
            ->times(3)
            ->andThrow(new \RuntimeException('Simulated delivery failure'));

        SendAssignmentNotificationJob::dispatch(
            (int) $assignment->id
        );

        $this->assertDatabaseCount('jobs', 1);

        $payload = json_decode(
            DB::table('jobs')->value('payload'),
            true
        );

        $this->assertSame(3, $payload['maxTries']);
        $this->assertSame('10', $payload['backoff']);

        foreach (range(1, 3) as $attempt) {
            $this->artisan('queue:work', [
                'connection' => 'database',
                '--once' => true,
                '--sleep' => 0,
            ])->assertSuccessful();

            if ($attempt < 3) {
                $this->assertDatabaseCount('jobs', 1);

                DB::table('jobs')->update([
                    'available_at' => time() - 1,
                ]);
            }
        }

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 1);

        $failedJob = DB::table('failed_jobs')->first();

        $this->assertNotNull($failedJob);

        $failedPayload = json_decode($failedJob->payload, true);

        $this->assertIsArray($failedPayload);

        $this->assertSame(
            SendAssignmentNotificationJob::class,
            $failedPayload['displayName'] ?? null
        );

        $this->assertSame(
            SendAssignmentNotificationJob::class,
            data_get($failedPayload, 'data.commandName')
        );
    }

    public function test_assignment_event_uses_private_channel_and_safe_broadcast_payload(): void
    {
        $assignment = Assignment::factory()->create();

        $event = new AssignmentCreated($assignment);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);

        $this->assertInstanceOf(
            PrivateChannel::class,
            $channels[0]
        );

        $this->assertSame([
            'assignment_id' => $assignment->id,
            'course_id' => $assignment->course_id,
            'title' => $assignment->title,
        ], $event->broadcastWith());
    }

    public function test_private_channel_allows_owner_and_enrolled_student_but_denies_others(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $student = User::factory()->create(['role' => 'mahasiswa']);
        $outsider = User::factory()->create(['role' => 'mahasiswa']);
        $admin = User::factory()->create(['role' => 'admin']);

        $course->students()->attach(
            $student->id,
            ['enrolled_at' => now()]
        );

        $authorizer = Broadcast::getChannels()
            ->get('course.{courseId}');

        $this->assertIsCallable($authorizer);

        $this->assertTrue($authorizer($lecturer, $course->id));
        $this->assertTrue($authorizer($student, $course->id));
        $this->assertFalse($authorizer($outsider, $course->id));
        $this->assertFalse($authorizer($admin, $course->id));
        $this->assertFalse($authorizer($lecturer, 999999));
    }
}