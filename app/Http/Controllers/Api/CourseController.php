<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    // no 8 mengubah *public function index(Request $request) { $courses = Course::where('status', 'active')->paginate(10); return CourseResource::collection($courses);}* menjadi *public function index(Request $request) { $courses = Course::with('lecturer')->withCount('students')->where('status', 'active')->paginate(10); return CourseResource::collection($courses);}* agar response API tidak bergantung langsung pada struktur model/database. *CourseResource* digunakan untuk mengontrol dan membatasi data yang diekspos kepada client serta menjaga konsistensi format response API. Selain itu, menambahkan *with('lecturer')* untuk memuat relasi dosen pengajar dan *withCount('students')* untuk menghitung jumlah mahasiswa yang terdaftar di setiap mata kuliah.
   public function index(Request $request)
    {
    $courses = Course::with('lecturer')
        ->withCount('students')
        ->where('status', 'active')
        ->paginate(10);

    return CourseResource::collection($courses);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Course::class);

        $validated = $request->validate([
            'code' => 'required|string|unique:courses,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks' => 'required|integer|min:1|max:6',
            'lecturer_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,active,archived',
        ]);

        $course = Course::create($validated);
        $course->load('lecturer');
    // no 3 Mengubah status code response dari 200 menjadi 201 (Created) karena endpoint
    // store() berhasil membuat resource baru. Status 201 menunjukkan bahwa data
    // course berhasil dibuat dan response berisi resource yang baru dibuat. *return (new CourseResource($course))->response()->setStatusCode(201);*
    return (new CourseResource($course))
        ->response()
        ->setStatusCode(201);
    }

    //no 1 a Method tersebut sebelumnya tidak mengembalikan data, Endpoint *show()* diubah dari mengembalikan model *course* secara langsung menjadi *CourseResource* agar response API tidak bergantung langsung pada struktur model/database. *CourseResource* digunakan untuk mengontrol dan membatasi data yang diekspos kepada client serta menjaga konsistensi format response API.
    public function show(Course $course)
    {
    Gate::authorize('view', $course);

    $course->load(['lecturer', 'materials', 'assignments']);

    return new CourseResource($course);
    }

    public function update(Request $request, Course $course)
    {
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'code' => 'required|string|unique:courses,code,'.$course->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sks' => 'required|integer|min:1|max:6',
            'lecturer_id' => 'required|exists:users,id',
            'status' => 'required|in:draft,active,archived',
        ]);

        $course->update($validated);
    //no 2 a mengubah *return $course;* menjadi *return new CourseResource($course);* agar response API tidak bergantung langsung pada struktur model/database. *CourseResource* digunakan untuk mengontrol dan membatasi data yang diekspos kepada client serta menjaga konsistensi format response API.
        return new CourseResource($course);
    }

    public function destroy(Course $course)
    {
        Gate::authorize('delete', $course);

        $course->delete();
    //no 4 mengubah *return response()->json(['message' => 'Mata kuliah berhasil dihapus'], 200);* menjadi *return response()->json(null, 204);* karena status code 204 (No Content) lebih tepat digunakan untuk response dari operasi delete yang berhasil. Status 204 menunjukkan bahwa resource berhasil dihapus dan tidak ada konten yang dikembalikan dalam response.
        return response()->json(null, 204);
    }
}
