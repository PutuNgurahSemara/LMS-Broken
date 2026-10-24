<?php

namespace Tests\Feature;

use App\Events\AssignmentCreated;
use App\Jobs\SendAssignmentNotificationJob;
use App\Listeners\SendAssignmentNotificationListener;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class W10QueueAndBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_created_queues_its_notification_listener(): void
    {
        Queue::fake();

        $assignment = Assignment::factory()->create();

        AssignmentCreated::dispatch($assignment);

        Queue::assertPushed(
            CallQueuedListener::class,
            fn (CallQueuedListener $listener): bool => $listener->class === SendAssignmentNotificationListener::class
        );
    }

    public function test_listener_dispatches_notification_job(): void
    {
        Bus::fake();
        $assignment = Assignment::factory()->create();

        (new SendAssignmentNotificationListener)->handle(new AssignmentCreated($assignment));

        Bus::assertDispatched(
            SendAssignmentNotificationJob::class,
            fn (SendAssignmentNotificationJob $job): bool => $job->assignment->is($assignment)
        );
        Bus::assertDispatchedTimes(SendAssignmentNotificationJob::class, 1);
    }

    public function test_notification_job_sends_to_enrolled_students_using_batch_notification(): void
    {
        Notification::fake();

        $course = Course::factory()->create();
        $students = User::factory()->count(2)->create();
        $otherUser = User::factory()->create();
        $course->students()->attach($students->modelKeys());

        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $job = new SendAssignmentNotificationJob($assignment);

        $this->assertSame(3, $job->tries);
        $job->handle();

        foreach ($students as $student) {
            Notification::assertSentToTimes($student, NewAssignmentNotification::class, 1);
        }

        Notification::assertNotSentTo($otherUser, NewAssignmentNotification::class);
    }

    public function test_failed_jobs_migration_has_columns_required_by_database_uuid_driver(): void
    {
        $this->assertTrue(Schema::hasColumns('failed_jobs', [
            'id',
            'uuid',
            'connection',
            'queue',
            'payload',
            'exception',
            'failed_at',
        ]));
        $this->assertSame('database-uuids', config('queue.failed.driver'));
        $this->assertSame('failed_jobs', config('queue.failed.table'));
    }

    public function test_assignment_broadcast_uses_private_course_channel(): void
    {
        $event = new AssignmentCreated(Assignment::factory()->create());

        $this->assertInstanceOf(PrivateChannel::class, $event->broadcastOn()[0]);
    }

    public function test_course_channel_authorization_uses_course_view_policy(): void
    {
        $channelCallbacks = Broadcast::getChannels();
        $authorizeCourseChannel = $channelCallbacks['course.{courseId}'];

        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $unregisteredStudent = User::factory()->create(['role' => 'mahasiswa']);
        $course->students()->attach($student->id);

        $this->assertTrue($authorizeCourseChannel($admin, $course->id));
        $this->assertTrue($authorizeCourseChannel($lecturer, $course->id));
        $this->assertTrue($authorizeCourseChannel($student, $course->id));
        $this->assertFalse($authorizeCourseChannel($unregisteredStudent, $course->id));
        $this->assertFalse($authorizeCourseChannel($student, $course->id + 999));
    }
}
