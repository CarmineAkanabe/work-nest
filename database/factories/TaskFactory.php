<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id'  => null, // always set explicitly in the seeder
            'assigned_to' => null, // always set explicitly in the seeder
            'title'       => fake()->sentence(4),
            'description' => fake()->sentences(7, true),
            "status" => TaskStatus::Pending,
            'deadline'    => fake()->dateTimeBetween('+1 week', '+2 months'),
        ];
    }
}
