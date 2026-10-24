<?php

namespace App\Listeners;

use App\Events\AssignmentCreated;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendAssignmentNotificationListener implements ShouldQueue
{
    public function handle(AssignmentCreated $event): void
    {
        $students = $event->assignment->course->students;

        Notification::send(
            $students, new NewAssignmentNotification($event->assignment));
    }
}
