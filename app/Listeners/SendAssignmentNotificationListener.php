<?php

namespace App\Listeners;

use App\Events\AssignmentCreated;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

// PERBAIKAN: Mengimplementasikan ShouldQueue agar listener berjalan di background/queue
class SendAssignmentNotificationListener implements ShouldQueue
{
    public function handle(AssignmentCreated $event): void
    {
        $students = $event->assignment->course->students;

        // PERBAIKAN: Menggunakan Notification::send() secara batch
        Notification::send(
            $students,
            new NewAssignmentNotification($event->assignment)
        );
    }
}