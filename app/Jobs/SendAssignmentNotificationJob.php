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

    // Masalah 6
    // (Sebelumnya properti $tries dihapus)
    // Penjelasan Masalah: Job tanpa properti $tries berpotensi mengulang tanpa batas (infinite retry loop) jika terjadi kegagalan permanen.
    // Perbaikan:
    public int $tries = 3;
    public int $backoff = 10;
    public int $timeout = 60;

    public function __construct(public Assignment $assignment)
    {
    }

    public function handle(): void
    {
        $students = $this->assignment->course->students;

        // Masalah 2
        // foreach ($students as $student) {
        //     $student->notify(new NewAssignmentNotification($this->assignment));
        // }
        // Penjelasan Masalah: Mengirim notifikasi dalam perulangan satu per satu menyebabkan masalah N+1 query pengiriman notifikasi individual ke database.
        // Perbaikan:
        Notification::send($students, new NewAssignmentNotification($this->assignment));
    }
}
