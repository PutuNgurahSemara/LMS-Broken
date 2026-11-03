<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendAssignmentNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    
    // PERBAIKAN: Menambahkan batas percobaan ulang sebanyak 3 kali
    public int $tries = 3;

    public function __construct(public Assignment $assignment)
    {
    }

    public function handle(): void
    {
        $students = $this->assignment->course->students;

        // PERBAIKAN: Menggunakan pengiriman batch Notification::send()
        Notification::send(
            $students,
            new NewAssignmentNotification($this->assignment)
        );
    }
}