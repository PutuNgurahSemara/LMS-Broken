<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\User;
use App\Models\Grade;
use Illuminate\Support\Facades\DB;

echo "=== QUERY ANALYSIS ===\n\n";

// Test 1: Index page queries
echo "TEST 1: Grade Recap Index Page - Query Details\n";
DB::enableQueryLog();

$courses = Course::with('lecturer:id,name')
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
    ->select('id', 'code', 'name', 'lecturer_id', 'status')
    ->paginate(15);

$queries = DB::getQueryLog();
DB::disableQueryLog();

foreach ($queries as $i => $query) {
    echo "Query " . ($i+1) . ":\n";
    echo $query['query'] . "\n";
    echo "Bindings: " . json_encode($query['bindings']) . "\n";
    echo "Time: " . $query['time'] . " ms\n\n";
}

// Test 2: Show page queries
echo "TEST 2: Grade Recap Show Page - Query Details\n";
DB::enableQueryLog();

$course = Course::first();
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

$queries = DB::getQueryLog();
DB::disableQueryLog();

foreach ($queries as $i => $query) {
    echo "Query " . ($i+1) . ":\n";
    echo $query['query'] . "\n";
    echo "Bindings: " . json_encode($query['bindings']) . "\n";
    echo "Time: " . $query['time'] . " ms\n\n";
}

echo "=== QUERY ANALYSIS COMPLETE ===\n";