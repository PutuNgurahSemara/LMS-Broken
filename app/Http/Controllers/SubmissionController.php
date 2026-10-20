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

        // [PERBAIKAN MASALAH #4 & #5]: Validasi MIME Type + Ekstensi & Ukuran Max 10MB (10240 KB)
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'file' => [
                'required',
                'file',
                'mimes:pdf,zip,rar,docx',
                'extensions:pdf,zip,rar,docx',
                'max:10240', // 10240 KB = 10 MB
            ],
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        // [PERBAIKAN MASALAH #6]: Ambil data submission lama (jika ada) untuk mencatat path berkas lama
        $submission = Submission::firstOrNew([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
        ]);

        $oldFilePath = $submission->file_path;

        // [PERBAIKAN MASALAH #1 & #3]: Disimpan di disk 'local' (privat) & menggunakan nama acak unik dari store()
        $filePath = $file->store('submissions', 'local');

        if (! $filePath) {
            throw new \RuntimeException('Gagal menyimpan berkas submission.');
        }

        // Simpan / Perbarui data ke Database
        $submission->fill([
            'file_path' => $filePath,
            'original_name' => $originalName,
            'file_size' => $fileSize,
            'notes' => $validated['notes'] ?? null,
            'submitted_at' => now(),
            'status' => 'submitted',
        ])->save();

        // [PERBAIKAN MASALAH #6]: Hapus berkas lama dari penyimpanan privat agar tidak menumpuk
        if ($oldFilePath && $oldFilePath !== $filePath && Storage::disk('local')->exists($oldFilePath)) {
            if (! Storage::disk('local')->delete($oldFilePath)) {
                logger()->error('Gagal menghapus berkas submission lama.', [
                    'path' => $oldFilePath,
                ]);
            }
        }

        return redirect()->route('courses.show', $assignment->course_id)->with('success', 'Tugas berhasil dikirim.');
    }

    public function download(Submission $submission)
    {
        // [PERBAIKAN MASALAH #2 & #7]: Wajib periksa otorisasi server-side lewat Gate Policy sebelum mengunduh
        Gate::authorize('download', $submission);

        // [PERBAIKAN MASALAH #1]: Memeriksa keberadaan berkas di disk 'local' (privat)
        if (! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path)) {
            abort(404, 'Berkas submission tidak ditemukan.');
        }

        // [PERBAIKAN MASALAH #1]: Mengunduh dari disk 'local' (privat)
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