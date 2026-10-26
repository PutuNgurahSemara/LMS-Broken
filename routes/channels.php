<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'course.{courseId}',
    function (User $user, int $courseId): bool {
    $course = Course::find($courseId);

    if (! $course) {
        return false;
    }

    $isLecturer = $user->role === 'dosen'
        && (int) $course->lecturer_id === (int) $user->id;

    $isEnrolledStudent = $user->role === 'mahasiswa'
        && $course->students()
            ->whereKey($user->id)
            ->exists();

    return $isLecturer || $isEnrolledStudent;
    }
);