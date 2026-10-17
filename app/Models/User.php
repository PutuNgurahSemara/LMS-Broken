<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected array $submissionFilePathsForDeletion = [];

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->submissionFilePathsForDeletion = $user->submissions()
                ->whereNotNull('file_path')
                ->pluck('file_path')
                ->all();
        });

        static::deleted(function (User $user): void {
            Submission::deleteStoredFiles($user->submissionFilePathsForDeletion);
        });
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'nim_nip',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function taughtCourses()
    {
        return $this->hasMany(Course::class, 'lecturer_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class)->withPivot('enrolled_at')->withTimestamps();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
