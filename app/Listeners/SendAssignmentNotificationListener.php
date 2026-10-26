<?php

namespace App\Listeners;

use App\Events\AssignmentCreated;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

// Masalah 1
// class SendAssignmentNotificationListener
// Penjelasan Masalah: Listener tidak mengimplementasikan interface ShouldQueue sehingga pengiriman notifikasi berjalan secara sinkron (blocking HTTP request). Dosen harus menunggu lama saat membuat tugas karena seluruh notifikasi diproses di siklus request.
// Perbaikan:
class SendAssignmentNotificationListener implements ShouldQueue
{
    // Masalah 6
    // (Sebelumnya tidak ada properti $tries dan $backoff)
    // Penjelasan Masalah: Tanpa properti $tries, job/listener yang gagal berpotensi mengulang tanpa batas (infinite retry loop) jika terjadi kegagalan permanen.
    // Perbaikan:
    public int $tries = 3;
    public int $backoff = 10;

    public function handle(AssignmentCreated $event): void
    {
        $students = $event->assignment->course->students;

        // Masalah 2
        // foreach ($students as $student) {
        //     $student->notify(new NewAssignmentNotification($event->assignment));
        // }
        // Penjelasan Masalah: Mengirim notifikasi di dalam perulangan foreach satu per satu memicu masalah N+1 database queries.
        // Perbaikan:
        Notification::send($students, new NewAssignmentNotification($event->assignment));
    }
}
