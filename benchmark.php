<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Grade;
use Illuminate\Support\Facades\DB;

echo "=== BENCHMARK GRADE RECAP ===\n\n";

// Test 1: Index page queries
echo "TEST 1: Grade Recap Index Page\n";
DB::enableQueryLog();

$startTime = microtime(true);
$startMemory = memory_get_usage();

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

$endTime = microtime(true);
$endMemory = memory_get_usage();

$queries = DB::getQueryLog();
DB::disableQueryLog();

echo "Queries executed: " . count($queries) . "\n";
echo "Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "Memory: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n\n";

// Test 2: Show page queries
echo "TEST 2: Grade Recap Show Page\n";
DB::enableQueryLog();

$course = Course::where('status', 'active')->first();
if (!$course) {
    echo "No active courses found, skipping...\n\n";
} else {
    $startTime = microtime(true);
    $startMemory = memory_get_usage();

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

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    echo "Queries executed: " . count($queries) . "\n";
    echo "Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "Memory: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n\n";
}

$endTime = microtime(true);
$endMemory = memory_get_usage();

$queries = DB::getQueryLog();
DB::disableQueryLog();

echo "Queries executed: " . count($queries) . "\n";
echo "Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "Memory: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n\n";

// Test 3: Users index with pagination
echo "TEST 3: Users Index (with pagination)\n";
DB::enableQueryLog();

$startTime = microtime(true);
$startMemory = memory_get_usage();

$users = User::query()->select('id', 'name', 'email', 'nim_nip', 'role', 'created_at')
    ->paginate(15);

$endTime = microtime(true);
$endMemory = memory_get_usage();

$queries = DB::getQueryLog();
DB::disableQueryLog();

echo "Queries executed: " . count($queries) . "\n";
echo "Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "Memory: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n\n";

// Test 4: Dashboard admin
echo "TEST 4: Dashboard Admin\n";
DB::enableQueryLog();

$startTime = microtime(true);
$startMemory = memory_get_usage();

$totalUsers = User::count();
$totalCourses = Course::count();
$recentCourses = Course::with('lecturer:id,name')->latest()->take(5)->get(['id', 'name', 'code', 'lecturer_id']);

$endTime = microtime(true);
$endMemory = memory_get_usage();

$queries = DB::getQueryLog();
DB::disableQueryLog();

echo "Queries executed: " . count($queries) . "\n";
echo "Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "Memory: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n\n";

echo "=== BENCHMARK COMPLETE ===\n";