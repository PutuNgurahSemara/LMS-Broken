<x-layout title="Edit Pengguna">
    <div class="max-w-xl mx-auto bg-white p-8 rounded-xl shadow-sm border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Data Pengguna</h1>

        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">
                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">
                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NIM / NIP</label>
                <input type="text" name="nim_nip" value="{{ old('nim_nip', $user->nim_nip) }}" class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">
                @error('nim_nip')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            @can('updateRole', $user)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Peran (Role)</label>
                <select name="role" required class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">
                    <option value="mahasiswa" {{ old('role', $user->role) === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                    <option value="dosen" {{ old('role', $user->role) === 'dosen' ? 'selected' : '' }}>Dosen</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            @endcan

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi Baru (Kosongkan jika tidak diubah)</label>
                <input type="password" name="password" class="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm">
                @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg">Batal</a>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-semibold text-sm rounded-lg shadow">Perbarui</button>
            </div>
        </form>
    </div>
</x-layout>
