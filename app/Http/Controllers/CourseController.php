<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CourseController extends Controller
{
    private array $courses = [
        [
            'id' => 1,
            'code' => 'SI2514024',
            'name' => 'Pemrograman Web',
            'sks' => 3,
            'lecturer' => 'Dr. Aris Sugiharto',
            'status' => 'active',
            'description' => '<script>alert("XSS oleh Ore")</script>'
        ],
        [
            'id' => 2,
            'code' => 'SI2514025',
            'name' => 'Basis Data Lanjut',
            'sks' => 3,
            'lecturer' => 'Budi Santoso, M.T.',
            'status' => 'active',
            'description' => 'Pembahasan indexing, transaksi, dan optimisasi query database relational.'
        ],
        [
            'id' => 3,
            'code' => 'SI2514026',
            'name' => 'Keamanan Informasi',
            'sks' => 2,
            'lecturer' => 'Siti Aminah, Ph.D.',
            'status' => 'draft',
            'description' => 'Konsep dasar enkripsi, OWASP Top 10, dan pencegahan XSS/CSRF.'
        ],
    ];

    public function index()
    {
        return view('courses.index', ['courses' => $this->courses]);
    }

    public function create()
    {
        return view('courses.create');
    }

    public function show($id)
    {
        $course = collect($this->courses)->firstWhere('id', (int) $id);
        if (!$course || $course['status'] !== 'active') {
            abort(404);
        }
        return view('courses.show', compact('course'));
    }

    public function destroy($id)
    {
        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil dihapus');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'sks' => 'required|integer|min:1|max:6',
            'lecturer' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // simpan ke $courses / DB sesuai kebutuhan tugas
        // ...

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil ditambahkan');

    }
}