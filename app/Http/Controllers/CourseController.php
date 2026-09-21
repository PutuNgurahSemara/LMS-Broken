<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('lecturer')->where('status', 'active')->get();
        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        $lecturers = User::where('role', 'dosen')->get();
        return view('courses.create', compact('lecturers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:courses,code',
            'name' => 'required',
            'sks' => 'required|numeric',
            'lecturer_id' => 'required|exists:users,id',
            'description' => 'nullable',
        ]);

        Course::create($validated);

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil ditambahkan');
    }

    public function show($id)
    {
        $course = Course::with(['lecturer', 'materials', 'assignments'])->find($id);
        if (!$course) {
            abort(404);
        }
        return view('courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        $lecturers = User::where('role', 'dosen')->get();
        return view('courses.edit', compact('course', 'lecturers'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'code' => 'required|unique:courses,code,' . $course->id,
            'name' => 'required',
            'sks' => 'required|numeric',
            'lecturer_id' => 'required|exists:users,id',
            'description' => 'nullable',
        ]);

        $course->update($validated);

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil diperbarui');
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil dihapus');
    }
}
