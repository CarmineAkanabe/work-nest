<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TaskService
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
        if ($user->role === UserRole::Admin || $user->role === UserRole::Manager) {
            return Task::with(['project', 'assignee'])->get();
        }

        return Task::with(['project', 'assignee'])
            ->where('assigned_to', $user->id)
            ->get();
    }

    public function create(array $data): Task
    {
        // return Task::create($data)->fresh();
        return Task::with(['project', 'assignee'])->find(
            Task::create($data)->id
        );
    }

    public function update(Task $task, array $data): Task
    {
        // $task->update($data);
        // return $task->fresh();
        $task->update($data);
        return $task->fresh()->load(['project', 'assignee']);
    }

    public function delete(Task $task)
    {
        $task->delete();
    }
}
