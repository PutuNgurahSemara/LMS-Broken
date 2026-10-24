<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Notifications\Notification;

class NewAssignmentNotification extends Notification
{
    public function __construct(public Assignment $assignment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'title' => $this->assignment->title,
            'course_name' => $this->assignment->course->name,
            'due_at' => $this->assignment->due_at->toIso8601String(),
            'message' => "Tugas baru '{$this->assignment->title}' telah ditambahkan.",
        ];
    }
}
