<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('course.{courseId}', function (User $user, int $courseId) {
    $course = Course::find($courseId);

    if (! $course) {
        return false;
    }

    return $user->role === 'admin'
        || $course->lecturer_id === $user->id
        || $course->students()->where('user_id', $user->id)->exists();
});