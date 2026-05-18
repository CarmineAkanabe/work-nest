<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;

use App\Enums\UserRole;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Created administrator
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@worknest.com',
            'role' => UserRole::Admin
        ]);

        // 2 managers, each with 2 projects
        $managers = User::factory(2)->create([
            'role' => UserRole::Manager
        ]);

        $projects = collect();

        foreach ($managers as $manager) {
            $managerProjects = Project::factory(2)->create([
                'user_id' => $manager->id,
            ]);
            $projects = $projects->merge($managerProjects);
        }

        // 2 Employees, each with 2 Tasks across the projects
        $employees = User::factory(2)->create([
            'role' => UserRole::Employee,
        ]);

        foreach ($employees as $employee) {
            Task::factory(2)->create([
                'assigned_to' => $employee->id,
                'project_id'  => $projects->random()->id,
            ]);
        }
    }
}
