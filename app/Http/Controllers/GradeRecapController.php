<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GradeRecapController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Course::class);

        $query = Course::with('lecturer:id,name')
            ->withCount([
                'assignments',
                'students',
                'assignments as submissions_count' => function ($q) {
                    $q->withCount('submissions');
                },
                'assignments as graded_count' => function ($q) {
                    $q->whereHas('submissions.grade');
                }
            ])
            ->where('status', 'active')
            ->select('id', 'code', 'name', 'lecturer_id', 'status');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('lecturer_id')) {
            $query->where('lecturer_id', $request->lecturer_id);
        }

        $courses = $query->paginate(15)->withQueryString();

        $lecturers = User::where('role', 'dosen')->get(['id', 'name']);

        return view('grade-recap.index', compact('courses', 'lecturers'));
    }

    public function show(Course $course)
    {
        Gate::authorize('view', $course);

        $course->load([
            'lecturer:id,name',
            'assignments:id,course_id,title,max_score,due_at',
            'students:id,name,nim_nip,email'
        ]);

        $assignments = $course->assignments;
        $assignmentsCount = $assignments->count();
        $students = $course->students;
        $studentsCount = $students->count();

        $studentIds = $students->pluck('id');
        $assignmentIds = $assignments->pluck('id');

        $grades = Grade::whereHas('submission', function ($q) use ($assignmentIds, $studentIds) {
            $q->whereIn('assignment_id', $assignmentIds)
              ->whereIn('user_id', $studentIds);
        })->with([
            'submission:id,assignment_id,user_id',
            'submission.student:id,name,nim_nip,email',
            'submission.assignment:id,title,max_score',
            'grader:id,name'
        ])->get();

        $gradesCount = $grades->count();

        // Group grades by student_id for easier access in view
        $gradesByStudent = $grades->groupBy('submission.user_id');

        // Calculate class average
        $avgPercentage = 0;
        if ($gradesCount > 0) {
            $avgPercentage = round($grades->avg(function ($grade) {
                return ($grade->score / $grade->submission->assignment->max_score) * 100;
            }), 1);
        }

        return view('grade-recap.show', compact(
            'course',
            'assignments',
            'assignmentsCount',
            'students',
            'studentsCount',
            'grades',
            'gradesCount',
            'gradesByStudent',
            'avgPercentage'
        ));
    }
}