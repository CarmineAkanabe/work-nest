<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null, // It's a better practice when seeding to avoid testing incremental issues
            'name' => fake()->sentence(3),
            // This gives more coherent sentences compared to realText() and paragraph()
            'description' => fake()->sentences(6, true),
            'status'      => ProjectStatus::Active,
            'deadline'    => fake()->dateTimeBetween('+1 month', '+6 months'),
        ];
    }
}
