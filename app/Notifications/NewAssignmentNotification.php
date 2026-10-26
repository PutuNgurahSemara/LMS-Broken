<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Assignment $assignment)
    {
    }

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
            // Masalah 3
            // 'student_grades' => $this->assignment->course->students->map(function ($student) {
            //     return [
            //         'student_id' => $student->id,
            //         'name' => $student->name,
            //         'score' => $student->submissions()->with('grade')->get()->pluck('grade.score')->filter()->avg(),
            //     ];
            // }),
            // Penjelasan Masalah: Kolom 'data' pada tabel notifications tidak dienkripsi. Menyimpan nilai mahasiswa lain di notifikasi melanggar privasi data akademik dan memicu query relasi N+1 yang sangat lambat. Sesuai modul 10, payload tidak boleh memuat nilai atau feedback.
            // Perbaikan:
            // Data 'student_grades' dihapus dari payload notifikasi dan hanya menyertakan data non-sensitif (assignment_id, title, course_name, due_at, message).
        ];
    }
}
