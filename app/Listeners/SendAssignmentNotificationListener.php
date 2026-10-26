<?php

namespace App\Listeners;

use App\Events\AssignmentCreated;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendAssignmentNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 10;

    public function handle(AssignmentCreated $event): void
    {
        $students = $event->assignment->course->students;

        // Send notifications in bulk to avoid N+1 queries
        Notification::send($students, new NewAssignmentNotification($event->assignment));
    }
}
