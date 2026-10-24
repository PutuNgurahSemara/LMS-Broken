<?php

namespace App\Listeners;

use App\Events\AssignmentCreated;
use App\Notifications\NewAssignmentNotification;

class SendAssignmentNotificationListener
{
    public function handle(AssignmentCreated $event): void
    {
        $students = $event->assignment->course->students;

        foreach ($students as $student) {
            $student->notify(new NewAssignmentNotification($event->assignment));
        }
    }
}
