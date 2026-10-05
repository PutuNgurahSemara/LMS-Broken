<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        // Bug N+1 FIX: Eager-load 'lecturer' untuk mencegah N+1 query.
        // Tanpa with('lecturer'), setiap Course akan memicu query terpisah untuk dosennya.
        // Jika ada 100 course → 101 query (1 + N). Dengan with() → selalu 2 query saja.
        $courses = Course::with('lecturer')->where('status', 'active')->paginate(10);

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

        // Bug 4 FIX: Status code diubah dari 200 menjadi 201 Created.
        // POST yang berhasil membuat resource baru seharusnya mengembalikan 201,
        // bukan 200. Client REST yang patuh standar HTTP akan menggunakan 201
        // sebagai sinyal bahwa resource baru berhasil dibuat di server.
        return response()->json(new CourseResource($course), 201);
    }

    public function show(Course $course)
    {
        Gate::authorize('view', $course);

        // Bug 1 FIX: Kembalikan CourseResource, bukan raw Eloquent model ($course).
        // Mengembalikan $course mentah akan mengekspos seluruh atribut internal model
        // (timestamps, field tersembunyi, metadata internal), dan format respons tidak
        // konsisten dengan endpoint lain. CourseResource memastikan hanya field yang
        // didefinisikan yang dikirim ke client.
        return new CourseResource($course->load('lecturer'));
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

        // Bug 2 FIX: Kembalikan CourseResource, bukan raw Eloquent model ($course).
        // Alasan sama dengan Bug 1 — $course mentah membocorkan struktur internal model
        // dan tidak konsisten dengan standar API resource yang sudah ditetapkan.
        return new CourseResource($course->load('lecturer'));
    }

    public function destroy(Course $course)
    {
        Gate::authorize('delete', $course);

        $course->delete();

        // Bug 5 FIX: Status code diubah dari 200 menjadi 204 No Content.
        // Operasi DELETE yang berhasil seharusnya 204 — tidak ada resource untuk
        // dikembalikan karena sudah dihapus. Mengembalikan 200 dengan body JSON
        // melanggar semantik HTTP REST karena menyiratkan ada konten yang direturn.
        return response()->noContent();
    }
}
