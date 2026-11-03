<x-layout title="Admin Dashboard">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Selamat datang, Administrator</h1>
        <p class="text-sm text-gray-500 mt-1">Ringkasan statistik dan aktivitas sistem KampusLMS</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <div class="text-xs uppercase font-semibold text-gray-500">Total Pengguna</div>
                <div class="text-3xl font-extrabold text-blue-600 mt-1">{{ $totalUsers }}</div>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <div class="text-xs uppercase font-semibold text-gray-500">Total Mata Kuliah</div>
                <div class="text-3xl font-extrabold text-green-600 mt-1">{{ $totalCourses }}</div>
            </div>
            <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Mata Kuliah Terbaru</h2>
        <div class="space-y-3">
            @foreach($recentCourses as $course)
            <div class="p-4 bg-gray-50 rounded-lg flex justify-between items-center">
                <div>
                    <span class="font-mono text-xs font-semibold text-blue-600 mr-2">{{ $course->code }}</span>
                    <a href="{{ route('courses.show', $course) }}" class="font-semibold text-gray-900 hover:underline">{{ $course->name }}</a>
                    <div class="text-xs text-gray-500 mt-0.5">Dosen: {{ $course->lecturer->name }}</div>
                </div>
                <a href="{{ route('courses.show', $course) }}" class="text-xs text-blue-600 hover:underline font-medium">Buka</a>
            </div>
            @endforeach
        </div>
    </div>
</x-layout>
