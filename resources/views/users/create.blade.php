<x-layout title="Tambah Pengguna">
    <div class="max-w-xl mx-auto bg-white p-8 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Tambah Pengguna Baru</h1>

        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">
                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">
                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NIM / NIP</label>
                <input type="text" name="nim_nip" value="{{ old('nim_nip') }}" placeholder="2406012..." class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">
                @error('nim_nip')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Peran (Role)</label>
                <select name="role" required class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="dosen">Dosen</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                <input type="password" name="password" required class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">
                @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg">Batal</a>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-semibold text-sm rounded-lg shadow">Simpan</button>
            </div>
        </form>
    </div>
</x-layout>
