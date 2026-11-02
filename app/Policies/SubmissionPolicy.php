<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    // PERBAIKAN: Method before() dihapus (atau hanya return true jika role admin, selain itu null)
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return null; // Lanjutkan pengecekan ke method otorisasi spesifik
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'dosen']);
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin' || $submission->assignment->course->lecturer_id === $user->id) {
            return true;
        }

        return $submission->user_id === $user->id;
    }

    public function download(User $user, Submission $submission): bool
    {
        return $this->view($user, $submission);
    }

    public function grade(User $user, Submission $submission): bool
    {
        return $user->role === 'admin' || $submission->assignment->course->lecturer_id === $user->id;
    }
}