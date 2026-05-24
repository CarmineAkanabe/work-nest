<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
    /**
     * fresh() goes back to the database, fetches the latest row, and returns a brand new model instance with the updated values
     */

    /**
     * Create a new class instance.
     */
    public function __construct()   {}

    public function getAll(User $user): Collection
    {
        // Admin sees all, Manager sees only their own
        if ($user->role === UserRole::Admin) {
            return Project::with('owner')->get();
        }

        return Project::with('owner')
            ->where('user_id', $user->id)
            ->get();
    }

    public function create(User $user, array $data): Project
    {
        return Project::create([
            ...$data,
            'user_id' => $user->id,
        ])->fresh();
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project->fresh();
    }

    public function delete(Project $project)
    {
        $project->delete();
    }
}
