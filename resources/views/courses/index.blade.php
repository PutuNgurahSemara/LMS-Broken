<x-layout title="Daftar Mata Kuliah">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Daftar Mata Kuliah</h1>
        <a href="{{ route('courses.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Tambah Mata Kuliah</a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- SEBELUMNYA SALAH:
         @php
             $activeCourses = array_filter($courses, function($c) {
                 return $c['status'] === 'active';
             });
         @endphp
         -> Logika filter status ditulis di dalam view, seharusnya di controller.
         PERBAIKAN: variabel $courses yang dikirim controller sudah difilter,
         jadi di sini tinggal @foreach ($courses as $course) langsung. --}}
    <table class="w-full bg-white rounded shadow overflow-hidden">
        <thead class="bg-gray-200 text-left">
            <tr>
                <th class="p-3">Kode</th>
                <th class="p-3">Nama Mata Kuliah</th>
                <th class="p-3">SKS</th>
                <th class="p-3">Dosen</th>
                <th class="p-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($courses as $course)
            <tr class="border-b">
                <td class="p-3">{{ $course['code'] }}</td>
                <td class="p-3">
                    {{-- SEBELUMNYA SALAH: href="/courses/{{ $course['id'] }}" (URL hardcode)
                         -> Kalau struktur URL berubah, link ini tidak ikut ter-update.
                         PERBAIKAN: pakai route('courses.show', ...) --}}
                    <a href="{{ route('courses.show', $course['id']) }}" class="text-blue-600 font-semibold hover:underline">
                        {{ $course['name'] }}
                    </a>
                </td>
                <td class="p-3">{{ $course['sks'] }}</td>
                <td class="p-3">{{ $course['lecturer'] }}</td>
                <td class="p-3 space-x-2">
                    {{-- SEBELUMNYA SALAH: href="/courses/{{ $course['id'] }}" (URL hardcode) --}}
                    <a href="{{ route('courses.show', $course['id']) }}" class="text-gray-600 hover:underline">Detail</a>

                    {{-- SEBELUMNYA SALAH:
                         <a href="/courses/{{ $course['id'] }}/delete" class="text-red-600 hover:underline"
                            onclick="return confirm('Hapus?')">Hapus</a>
                         -> Link <a> memicu request GET untuk menghapus data, berbahaya
                            karena bisa terpicu otomatis oleh crawler/prefetch/preview link.
                         PERBAIKAN: ganti jadi <form> dengan method POST + @method('DELETE'). --}}
                    <form action="{{ route('courses.destroy', $course['id']) }}" method="POST" class="inline"
                          onsubmit="return confirm('Hapus?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</x-layout>
