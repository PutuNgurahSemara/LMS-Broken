<x-layout title="{{ $assignment->title }}">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <span class="px-2.5 py-1 text-xs font-semibold bg-gray-100 text-gray-700 rounded-md">
                    Mata Kuliah: {{ $assignment->course->name }}
                </span>
                <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $assignment->title }}</h1>
                <p class="text-xs text-gray-500 mt-1">
                    Batas Waktu Pengumpulan: <strong class="text-red-600">{{ $assignment->due_at->format('d M Y H:i') }}</strong> &bull; Nilai Maksimal: {{ $assignment->max_score }}
                </p>
            </div>
            <a href="{{ route('courses.show', $assignment->course_id) }}" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200">
                &larr; Kembali ke Course
            </a>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100 text-sm text-gray-700">
            <h2 class="font-semibold text-gray-900 mb-1">Instruksi Tugas:</h2>
            <p>{{ $assignment->description ?? 'Tidak ada instruksi khusus.' }}</p>
        </div>
    </div>

    <!-- Student Submission Area -->
    @if(auth()->user()->role === 'mahasiswa')
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Pengumpulan Tugas Anda</h2>

        @php
            $mySubmission = $assignment->submissions->where('user_id', auth()->id())->first();
        @endphp

        @if($mySubmission)
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex justify-between items-center">
                <div>
                    <div class="text-sm font-semibold text-green-900">Tugas Telah Dikirim</div>
                    <div class="text-xs text-green-700 mt-0.5">Dikirim pada: {{ $mySubmission->submitted_at->format('d M Y H:i') }}</div>
                    <div class="text-xs text-gray-600 mt-1 font-mono">Berkas: {{ $mySubmission->original_name }}</div>
                </div>
                <a href="{{ route('submissions.download', $mySubmission) }}" class="px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700">
                    Unduh Berkas Saya
                </a>
            </div>

            @if($mySubmission->grade)
            <div class="mt-4 pt-3 border-t border-green-200">
                <div class="text-sm font-bold text-gray-900">Nilai: <span class="text-blue-600 text-base">{{ $mySubmission->grade->score }} / {{ $assignment->max_score }}</span></div>
                @if($mySubmission->grade->feedback)
                <p class="text-xs text-gray-600 mt-1">Catatan Dosen: "{{ $mySubmission->grade->feedback }}"</p>
                @endif
            </div>
            @else
            <div class="mt-2 text-xs text-amber-700">Belum dinilai oleh dosen.</div>
            @endif
        </div>
        @else
        <!-- Upload Form -->
        <form action="{{ route('submissions.store', $assignment) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unggah Berkas Tugas (PDF/ZIP max 10MB)</label>
                <input type="file" name="file" required class="w-full text-sm text-gray-500 border border-gray-300 rounded-lg p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"></textarea>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white font-semibold text-sm rounded-lg shadow hover:bg-blue-700">
                Kirim Tugas
            </button>
        </form>
        @endif
    </div>
    @endif

    <!-- Lecturer / Admin Grading Area -->
    @if(in_array(auth()->user()->role, ['admin', 'dosen']))
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Daftar Pengumpulan Mahasiswa</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3">Mahasiswa</th>
                        <th class="px-4 py-3">Waktu Pengumpulan</th>
                        <th class="px-4 py-3">Berkas</th>
                        <th class="px-4 py-3">Status / Nilai</th>
                        <th class="px-4 py-3 text-right">Penilaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($assignment->submissions as $submission)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">{{ $submission->student->name }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ $submission->student->nim_nip }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ $submission->submitted_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('submissions.download', $submission) }}" class="text-blue-600 text-xs font-medium hover:underline flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>{{ $submission->original_name }}</span>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            @if($submission->grade)
                            <span class="px-2.5 py-1 text-xs font-bold bg-green-100 text-green-800 rounded-full">
                                {{ $submission->grade->score }} / {{ $assignment->max_score }}
                            </span>
                            @else
                            <span class="px-2.5 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded-full">Belum Dinilai</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form action="{{ route('submissions.grade', $submission) }}" method="POST" class="inline-flex items-center space-x-2">
                                @csrf
                                <input type="number" name="score" value="{{ $submission->grade->score ?? '' }}" placeholder="Score" min="0" max="{{ $assignment->max_score }}" required
                                    class="w-16 px-2 py-1 border border-gray-300 rounded text-xs">
                                <input type="text" name="feedback" value="{{ $submission->grade->feedback ?? '' }}" placeholder="Feedback"
                                    class="w-32 px-2 py-1 border border-gray-300 rounded text-xs">
                                <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700">Simpan</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">Belum ada pengumpulan tugas dari mahasiswa.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</x-layout>
