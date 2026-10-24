<?php

namespace App\Listeners;

use App\Events\AssignmentCreated;
use App\Notifications\NewAssignmentNotification;

class SendAssignmentNotificationListener implements ShouldQueue
{
    use Queueable;

    public function handle(AssignmentCreated $event): void
    {
        $event->assignment->loadMissing('course.students');

        Notification::send($event->assignment->course->students, new NewAssignmentNotification($event->assignment));
    }
}
