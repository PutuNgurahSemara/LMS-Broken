<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = ['assignment_id', 'user_id', 'file_path', 'original_name', 'file_size', 'status', 'notes', 'submitted_at'];

    protected static function booted(): void
    {
        static::deleted(function (Submission $submission): void {
            self::deleteStoredFiles([$submission->file_path]);
        });
    }

    public static function deleteStoredFiles(iterable $paths): void
    {
        $paths = collect($paths)->filter()->unique()->values()->all();

        if ($paths === []) {
            return;
        }

        foreach ($paths as $path) {
            foreach (['local', 'public'] as $disk) {
                $storage = Storage::disk($disk);

                if ($storage->exists($path) && ! $storage->delete($path)) {
                    throw new RuntimeException("Unable to delete submission file [{$path}] from [{$disk}].");
                }
            }
        }
    }

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }
}
