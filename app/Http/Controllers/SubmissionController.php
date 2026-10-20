<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubmissionController extends Controller
{
    public function store(Request $request, Assignment $assignment)
    {
        $user = $request->user();

        // Check if student is enrolled in course
        if ($user->role === 'mahasiswa' && ! $assignment->course->students()->where('user_id', $user->id)->exists()) {
            abort(403, 'Anda tidak terdaftar dalam mata kuliah ini.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
            // NOTE PERBAIKAN:
            // 1. Ganti 'extensions' ke 'mimes' agar memvalidasi MIME type asli file, bukan hanya akhiran string.
            // 2. Ganti 'max:10' ke 'max:10240' karena satuan Laravel adalah KB (10 KB -> 10240 KB = 10 MB).
            'file' => 'required|file|mimes:pdf,zip,rar,docx|max:10240',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // NOTE PERBAIKAN:
        // 3. Jangan pakai $originalName langsung karena rawan tabrakan nama file & path traversal.
        //    Gunakan hash acak unik (Str::random(40)).
        $hashedName = Str::random(40).'.'.$file->getClientOriginalExtension();

        // 4. Ganti disk dari 'public' ke 'local' agar tugas mahasiswa tidak bocor ke publik via URL.
        $filePath = $file->storeAs('submissions', $hashedName, 'local');


        Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $user->id],
            [
                'file_path' => $filePath,
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'notes' => $validated['notes'] ?? null,
                'submitted_at' => now(),
                'status' => 'submitted',
            ]
        );

        return redirect()->route('courses.show', $assignment->course_id)->with('success', 'Tugas berhasil dikirim.');
    }

    public function download(Submission $submission)
    {
        // NOTE PERBAIKAN:
        // 5. Tambahkan otorisasi Gate agar mahasiswa lain tidak bisa mengunduh berkas milik orang lain (cegah IDOR).
        Gate::authorize('download', $submission);

        // NOTE PERBAIKAN:
        // Ganti disk dari 'public' ke 'local' sesuai lokasi penyimpanan berkas privat.
        if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
            abort(404, 'Berkas submission tidak ditemukan.');
        }

        return Storage::disk('local')->download($submission->file_path, $submission->original_name);
    }

    public function grade(Request $request, Submission $submission)
    {
        Gate::authorize('grade', $submission);

        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:'.$submission->assignment->max_score,
            'feedback' => 'nullable|string',
        ]);

        Grade::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'graded_by' => $request->user()->id,
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? null,
                'graded_at' => now(),
            ]
        );

        $submission->update(['status' => 'graded']);

        return redirect()->back()->with('success', 'Nilai berhasil disimpan.');
    }
}
