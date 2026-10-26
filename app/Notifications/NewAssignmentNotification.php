<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewAssignmentNotification extends Notification
{
    public function __construct(
        public int $assignmentId,
        public string $title,
        public string $courseName,
        public string $dueAt,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'assignment_id' => $this->assignmentId,
            'title' => $this->title,
            'course_name' => $this->courseName,
            'due_at' => $this->dueAt,
            'message' => "Tugas baru '{$this->title}' telah ditambahkan.",
        ];
    }
}