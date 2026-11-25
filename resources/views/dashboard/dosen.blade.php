<x-layout title="Dashboard Dosen">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Selamat datang, {{ auth()->user()->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">Panel pengajaran dan pengampuan mata kuliah Anda</p>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Mata Kuliah Yang Anda Ampu</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($courses as $course)
            <div class="p-5 border border-gray-200 rounded-xl hover:border-blue-500 transition bg-gray-50/50">
                <span class="font-mono text-xs font-bold text-blue-600 px-2 py-0.5 bg-blue-50 rounded">{{ $course->code }}</span>
                <h3 class="font-bold text-gray-900 text-lg mt-2">{{ $course->name }}</h3>
                <p class="text-xs text-gray-500 mt-1">{{ $course->sks }} SKS &bull; {{ $course->students_count }} Mahasiswa Terdaftar</p>
                <div class="mt-4 pt-3 border-t border-gray-200 flex justify-between items-center">
                    <a href="{{ route('courses.show', $course) }}" class="text-xs font-semibold text-blue-600 hover:underline">Kelola Kelas &rarr;</a>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400">Anda belum mengampu mata kuliah apapun.</p>
            @endforelse
        </div>
    </div>
</x-layout>
