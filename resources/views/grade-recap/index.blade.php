<x-layout title="Rekap Nilai Mata Kuliah">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Rekap Nilai Mata Kuliah</h1>
                <p class="text-sm text-gray-500 mt-1">Ringkasan nilai seluruh mata kuliah aktif</p>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('grade-recap.index') }}" class="mt-6 flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cari Mata Kuliah</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau kode mata kuliah..."
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="md:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dosen</label>
                <select name="lecturer_id" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Dosen</option>
                    @foreach($lecturers as $lecturer)
                        <option value="{{ $lecturer->id }}" {{ request('lecturer_id') == $lecturer->id ? 'selected' : '' }}>
                            {{ $lecturer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="md:w-32 flex items-end">
                <button type="submit" class="w-full px-4 py-2.5 bg-blue-600 text-white font-semibold text-sm rounded-lg hover:bg-blue-700">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Courses Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Mata Kuliah</th>
                        <th class="px-4 py-3">Dosen</th>
                        <th class="px-4 py-3 text-center">Mahasiswa</th>
                        <th class="px-4 py-3 text-center">Tugas</th>
                        <th class="px-4 py-3 text-center">Pengumpulan</th>
                        <th class="px-4 py-3 text-center">Ternilai</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($courses as $course)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-sm font-semibold text-blue-800">{{ $course->code }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $course->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $course->lecturer->name }}</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $course->students_count }}</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $course->assignments_count }}</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $course->submissions_count }}</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $course->graded_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('grade-recap.show', $course) }}"
                                class="px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-200">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">Belum ada mata kuliah aktif.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{ $courses->links() }}
    </div>
</x-layout>