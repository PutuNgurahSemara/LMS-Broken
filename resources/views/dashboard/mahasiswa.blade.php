<x-layout title="Dashboard Mahasiswa">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Halo, {{ auth()->user()->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">NIM: <span class="font-mono font-semibold">{{ auth()->user()->nim_nip }}</span> &bull; Panel Pembelajaran Mahasiswa</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Mata Kuliah Diikuti</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($enrolledCourses as $course)
                    <div class="p-4 border border-gray-200 rounded-xl hover:border-blue-500 transition">
                        <span class="font-mono text-xs font-bold text-blue-600 px-2 py-0.5 bg-blue-50 rounded">{{ $course->code }}</span>
                        <h3 class="font-bold text-gray-900 text-base mt-2">{{ $course->name }}</h3>
                        <p class="text-xs text-gray-500 mt-1">Dosen: {{ $course->lecturer->name }}</p>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <a href="{{ route('courses.show', $course) }}" class="text-xs font-semibold text-blue-600 hover:underline">Masuk Kelas &rarr;</a>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400">Belum terdaftar di mata kuliah manapun.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Tugas Mendatang</h2>
                <div class="space-y-3">
                    @forelse($upcomingAssignments as $assignment)
                    <div class="p-3 bg-amber-50/60 border border-amber-200/60 rounded-lg">
                        <a href="{{ route('assignments.show', $assignment) }}" class="font-semibold text-sm text-gray-900 hover:text-blue-600">
                            {{ $assignment->title }}
                        </a>
                        <div class="text-xs text-amber-800 mt-1">Deadline: {{ $assignment->due_at->format('d M Y H:i') }}</div>
                    </div>
                    @empty
                    <p class="text-xs text-gray-400">Tidak ada tugas mendatang.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layout>
