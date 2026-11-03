<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\User;
use App\Models\Grade;
use Illuminate\Support\Facades\DB;

echo "=== BASELINE BENCHMARK (BEFORE FIXES) ===\n\n";

// Simulate the problematic queries BEFORE fixes

// Test 1: OLD Dashboard admin - User::all()->count()
echo "TEST 1: OLD Dashboard Admin (User::all()->count())\n";
DB::enableQueryLog();

$startTime = microtime(true);
$startMemory = memory_get_usage();

$totalUsers = User::all()->count();
$totalCourses = Course::all()->count();
$recentCourses = Course::with('lecturer')->latest()->take(5)->get();

$endTime = microtime(true);
$endMemory = memory_get_usage();

$queries = DB::getQueryLog();
DB::disableQueryLog();

echo "Queries executed: " . count($queries) . "\n";
foreach ($queries as $i => $query) {
    echo "  Query " . ($i+1) . ": " . $query['query'] . " (" . $query['time'] . " ms)\n";
}
echo "Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "Memory: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n\n";

// Test 2: OLD Users index - no pagination, select *
echo "TEST 2: OLD Users Index (no pagination, select *)\n";
DB::enableQueryLog();

$startTime = microtime(true);
$startMemory = memory_get_usage();

$users = User::query()->get();

$endTime = microtime(true);
$endMemory = memory_get_usage();

$queries = DB::getQueryLog();
DB::disableQueryLog();

echo "Queries executed: " . count($queries) . "\n";
foreach ($queries as $i => $query) {
    echo "  Query " . ($i+1) . ": " . $query['query'] . " (" . $query['time'] . " ms)\n";
}
echo "Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
echo "Memory: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n\n";

// Test 3: OLD Course show with N+1
echo "TEST 3: OLD Course Show (N+1 problem)\n";
DB::enableQueryLog();

$course = Course::where('status', 'active')->first();
if (!$course) {
    echo "No active courses found, skipping...\n\n";
} else {
    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    $course->load(['lecturer', 'materials', 'assignments.submissions.student']);

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    echo "Queries executed: " . count($queries) . "\n";
    foreach ($queries as $i => $query) {
        echo "  Query " . ($i+1) . ": " . $query['query'] . " (" . $query['time'] . " ms)\n";
    }
    echo "Time: " . round(($endTime - $startTime) * 1000, 2) . " ms\n";
    echo "Memory: " . round(($endMemory - $startMemory) / 1024 / 1024, 2) . " MB\n\n";
}

echo "=== BASELINE COMPLETE ===\n";