<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\User;

$course = Course::where('status', 'active')->first();
if (!$course) {
    $dosen = User::where('role', 'dosen')->first();
    $course = Course::create([
        'code' => 'TEST101',
        'name' => 'Test Course',
        'description' => 'Test Description',
        'sks' => 3,
        'lecturer_id' => $dosen->id,
        'status' => 'active'
    ]);
    
    // Create assignments
    for ($i = 1; $i <= 3; $i++) {
        $assignment = $course->assignments()->create([
            'title' => 'Assignment ' . $i,
            'description' => 'Description ' . $i,
            'due_at' => now()->addDays(7),
            'max_score' => 100
        ]);
    }
    
    // Enroll students
    $students = User::where('role', 'mahasiswa')->take(10)->get();
    $course->students()->attach($students->pluck('id'));
    
    echo 'Created test course with assignments and students';
} else {
    echo 'Course already exists: ' . $course->name;
}