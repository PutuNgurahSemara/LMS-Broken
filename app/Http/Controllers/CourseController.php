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
        // 1. Baca filter langsung dari request (tanpa session)
        $search = $request->input('search');
        $status = $request->input('status', 'active');

        $query = Course::with('lecturer')->withCount('students');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        // 2. Pertahankan query string pada link pagination
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
        // 3. Tambahkan validasi server-side
        $validated = $request->validate([
            'code'        => 'required|string|max:20|unique:courses,code',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks'         => 'required|integer|min:1|max:6',
            'lecturer_id' => 'required|exists:users,id',
            'status'      => 'required|in:draft,active,archived',
        ]);

        Course::create($validated);

        // 4. Terapkan pola PRG (Post/Redirect/Get)
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
        // 5. Abaikan ID course saat ini pada pemeriksaan unik
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:20', Rule::unique('courses', 'code')->ignore($course->id)],
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks'         => 'required|integer|min:1|max:6',
            'lecturer_id' => 'required|exists:users,id',
            'status'      => 'required|in:draft,active,archived',
        ]);

        $course->update($validated);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
}