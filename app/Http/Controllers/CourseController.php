<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        /*
        if ($request->has('search')) {
            session(['course_search' => $request->search]);
        }
        if ($request->has('status')) {
            session(['course_status' => $request->status]);
        }
        kode sebelum perbaikan */

        /*
        $search = session('course_search');
        $status = session('course_status', 'active');
        kode sebelum perbaikan*/


        $query = Course::with('lecturer')->withCount('students');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

       /* if ($status) {
            $query->where('status', $status);
        }
        kode sebelum perbaikan */

        if ($request->filled('status')) {
        $query->where('status', $request->status); //kode setelah perbaikan
    }

        // $courses = $query->paginate(10); kode sebelum perbaikan

        $courses = $query->paginate(10)->withQueryString(); //kode setelah perbaikan

        // return view('courses.index', compact('courses')); kode sebelum perbaikan

         return redirect()
        ->route('courses.index')
        ->with('success', 'Mata kuliah berhasil ditambahkan.'); //kode setelah perbaikan
    }

    public function create()
    {
        $lecturers = User::where('role', 'dosen')->get();

        return view('courses.create', compact('lecturers'));
    }

    public function store(Request $request)
    {
        //Course::create([ kode seblum perbaikan
         $validated = $request->validate([ //kode setelah perbaikan
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'sks' => $request->sks,
            'lecturer_id' => $request->lecturer_id,
            'status' => $request->status ?? 'draft',
        ]);

        // $courses = Course::paginate(10); kode salah
        Course::create($validated); // kode benar

        $query = Course::with('lecturer')->withCount('students');

         if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'like', '%'.$request->search.'%')
              ->orWhere('code', 'like', '%'.$request->search.'%');
         });
        }

        if ($request->filled('status')) {
        $query->where('status', $request->status);
        }

    $courses = $query->paginate(10)->withQueryString();

    return view('courses.index', compact('courses'));
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
        /* $validated = $request->validate([
            'code' => 'required|string|max:20|unique:courses,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks' => 'required|integer|min:1|max:6',
            'lecturer_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,active,archived',
        ]);
        kode sebelum perbaikan */ 

       $validated = $request->validate([
        'code' => [
            'required',
            'string',
            'max:20',
            Rule::unique('courses', 'code')->ignore($course->id),
        ],
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
        'sks'         => 'required|integer|min:1|max:6',
        'lecturer_id' => 'required|exists:users,id',
        'status'      => 'required|in:draft,active,archived',
    ]); // kode setelah perbaikan
 
        $course->update($validated);

        return redirect()->route('courses.show', $course)->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
