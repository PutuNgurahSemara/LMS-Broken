<x-layout title="Tambah Mata Kuliah">
    <div class="bg-white p-6 rounded shadow max-w-lg mx-auto">
        <h1 class="text-2xl font-bold mb-4">Tambah Mata Kuliah</h1>
        <form action="{{ route('courses.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block mb-1 font-medium">Kode Mata Kuliah</label>
                <input type="text" name="code" value="{{ old('code') }}" class="w-full border rounded p-2" placeholder="SI2514027" required>
                @error('code')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block mb-1 font-medium">Nama Mata Kuliah</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded p-2" placeholder="Pemrograman Web Lanjut" required>
                @error('name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block mb-1 font-medium">SKS</label>
                <input type="number" name="sks" value="{{ old('sks') }}" class="w-full border rounded p-2" placeholder="3" required>
                @error('sks')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block mb-1 font-medium">Dosen Pengampu</label>
                <input type="text" name="lecturer" value="{{ old('lecturer') }}" class="w-full border rounded p-2" placeholder="Nama Dosen" required>
                @error('lecturer')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block mb-1 font-medium">Deskripsi</label>
                <textarea name="description" class="w-full border rounded p-2" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end space-x-2">
                <a href="{{ route('courses.index') }}" class="px-4 py-2 border rounded">Batal</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
            </div>
        </form>
    </div>
</x-layout>
