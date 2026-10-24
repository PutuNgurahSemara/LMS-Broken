<?php

namespace App\Listeners;

use App\Events\AssignmentCreated;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAssignmentNotificationListener implements ShouldQueue
{
    public function handle(AssignmentCreated $event): void
    {
        $students = $event->assignment->course->students;

        foreach ($students as $student) {
            $student->notify(new NewAssignmentNotification($event->assignment));
        }
    }
}
