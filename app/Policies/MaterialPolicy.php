<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    public function view(User $user, Material $material): bool
    {
        if ($user->role === 'admin' || $material->course->lecturer_id === $user->id) {
            return true;
        }

        return $material->course->students()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'dosen']);
    }

    public function delete(User $user, Material $material): bool
    {
        return $user->role === 'admin' || $material->course->lecturer_id === $user->id || $material->uploaded_by === $user->id;
    }

    // FIX: Menambahkan method download() untuk memastikan hanya Admin, Dosen Pengampu, atau Mahasiswa terdaftar yang bisa mengunduh
    public function download(User $user, Material $material): bool
    {
        $course = $material->course;

        return $user->role === 'admin'
            || (int) $course->lecturer_id === (int) $user->id
            || $course->students()->where('user_id', $user->id)->exists();
    }
}