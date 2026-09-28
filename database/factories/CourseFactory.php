<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'code' => 'CS'.fake()->unique()->numberBetween(100, 999),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'sks' => fake()->numberBetween(1, 4),
            'lecturer_id' => User::factory(),
            'status' => 'active',
        ];
    }
}
