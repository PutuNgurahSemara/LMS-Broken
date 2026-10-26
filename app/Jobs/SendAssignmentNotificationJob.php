<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendAssignmentNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public int $timeout = 60;

    public function __construct(public int $assignmentId)
    {
    }

    public function handle(): void
    {
        $assignment = Assignment::with('course')
            ->find($this->assignmentId);

        if (! $assignment) {
            return;
        }

        $students = $assignment->course->students()->get();

        if ($students->isEmpty()) {
            return;
        }

        $notification = new NewAssignmentNotification(
            assignmentId: (int) $assignment->id,
            title: (string) $assignment->title,
            courseName: (string) $assignment->course->name,
            dueAt: $assignment->due_at->toIso8601String(),
        );

        Notification::send($students, $notification);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Pengiriman notifikasi tugas gagal setelah percobaan ulang.', [
            'assignment_id' => $this->assignmentId,
            'exception_class' => $exception::class,
        ]);
    }
}