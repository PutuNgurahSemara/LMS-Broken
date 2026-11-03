<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $totalUsers = User::count();
            $totalCourses = Course::count();
            $recentCourses = Course::with('lecturer:id,name')->latest()->take(5)->get(['id', 'name', 'code', 'lecturer_id']);

            return view('dashboard.admin', compact('totalUsers', 'totalCourses', 'recentCourses'));
        }

        if ($user->role === 'dosen') {
            $courses = Course::where('lecturer_id', $user->id)
                ->withCount('students')
                ->get(['id', 'name', 'code']);

            $recentSubmissions = Assignment::whereIn('course_id', $courses->pluck('id'))
                ->with(['submissions.student:id,name', 'submissions.grade:id,submission_id,score'])
                ->latest()
                ->take(10)
                ->get(['id', 'title', 'course_id', 'max_score']);

            return view('dashboard.dosen', compact('courses', 'recentSubmissions'));
        }

        // Mahasiswa
        $enrolledCourses = $user->courses()->with('lecturer:id,name')->get(['id', 'name', 'code', 'lecturer_id']);
        $upcomingAssignments = Assignment::whereIn('course_id', $enrolledCourses->pluck('id'))
            ->where('due_at', '>=', now())
            ->orderBy('due_at')
            ->get(['id', 'title', 'course_id', 'due_at', 'max_score']);

        return view('dashboard.mahasiswa', compact('enrolledCourses', 'upcomingAssignments'));
    }
}
