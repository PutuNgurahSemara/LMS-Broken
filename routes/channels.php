<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('course.{courseId}', function (User $user, int $courseId): bool {
    $course = Course::find($courseId);

    return $course !== null && Gate::forUser($user)->allows('view', $course);
});
