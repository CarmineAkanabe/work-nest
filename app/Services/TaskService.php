<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Events\TaskCompleted;
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
        // Variable for the status before update
        $previousStatus = $task->status;

        $task->update($data);
        $task = $task->fresh()->load(['project', 'assignee']);

        // This is where we initiate the TaskCompleted event and listeners Only if task is updated to Completed
        // (if it was completed b4 It is left alone)
        if (isset($data['status']) && $data['status'] === TaskStatus::Completed->value && $previousStatus !== TaskStatus::Completed) {
            TaskCompleted::dispatch($task);
        }

        return $task;
    }

    public function delete(Task $task)
    {
        $task->delete();
    }
}
