<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'description', 'due_at', 'max_score'];

    protected array $submissionFilePathsForDeletion = [];

    protected static function booted(): void
    {
        static::deleting(function (Assignment $assignment): void {
            $assignment->submissionFilePathsForDeletion = $assignment->submissions()
                ->whereNotNull('file_path')
                ->pluck('file_path')
                ->all();
        });

        static::deleted(function (Assignment $assignment): void {
            Submission::deleteStoredFiles($assignment->submissionFilePathsForDeletion);
        });
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
