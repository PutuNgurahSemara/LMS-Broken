<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request)
    {
    // MASALAH 3 NIHH: Filter tidak lagi disimpan di session. Search dan status langsung diambil dari query parameter URL.
        $search = $request->input('search');
        $status = $request->input('status', 'active');

        $query = Course::with('lecturer')->withCount('students');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        // MASALAH 4 NIHH: Mempertahankan query string search dan status saat berpindah halaman pagination.
        $courses = $query->paginate(10)->withQueryString();

        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.create', compact('lecturers'));
    }

    public function store(Request $request)
    {
        // MASALAH 1 NIHH: validasi dilakukan di backend agar data yang masuk ke database tetap aman
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:courses,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks' => 'required|integer|min:1|max:6',
            'lecturer_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,active,archived',
        ]);

        Course::create($validated);

        // MASALAH 5 NIHH: Setelah data berhasil disimpan, lakukan redirect ke halaman daftar. Hal ini mencegah halaman hasil POST di-refresh dan dikirim ulang.
        return redirect()
            ->route('courses.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function show(Course $course)
    {
        $course->load(['lecturer', 'materials', 'assignments.submissions']);

        return view('courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    public function update(Request $request, Course $course)
    {
        // MASALAH 2 NIHH: Validasi unique harus mengabaikan course yang sedang diedit. Tanpa ignore(), kode milik sendiri dianggap sudah digunakan.
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('courses', 'code')->ignore($course->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks' => 'required|integer|min:1|max:6',
            'lecturer_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,active,archived',
        ]);

        $course->update($validated);

        return redirect()->route('courses.show', $course)
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
