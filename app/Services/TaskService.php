<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Events\TaskCompleted;
use App\Models\Task;
use App\Models\User;
use Cache;
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

    // Full cache implementation by ID
    public function getAll(User $user): Collection
    {

        $cacheKey = "tasks.user.{$user->id}";

        $cachedIds = Cache::get($cacheKey);

        if ($cachedIds) {
            return Task::with(['project', 'assignee'])
                ->whereIn('id', json_decode($cachedIds, true))
                ->get();
        }

        $tasks = ($user->role === UserRole::Admin || $user->role === UserRole::Manager)
            ? Task::with(['project', 'assignee'])->get()
            : Task::with(['project', 'assignee'])->where('assigned_to', $user->id)->get();

        Cache::put($cacheKey, $tasks->pluck('id')->toJson(), now()->addMinutes(10));

        return $tasks;

        // Cache key to be used (Cache fails because redis can't receive raw collections)
        // $cacheKey = "tasks.user.{$user->id}";

        // // Implementation with Caching
        // return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
        //     if ($user->role === UserRole::Admin || $user->role === UserRole::Manager) {
        //         return Task::with(['project', 'assignee'])->get();
        //     }

        //     return Task::with(['project', 'assignee'])
        //         ->where('assigned_to', $user->id)
        //         ->get();
        // });

        // Old approach
        // if ($user->role === UserRole::Admin || $user->role === UserRole::Manager) {
        //     return Task::with(['project', 'assignee'])->get();
        // }

        // return Task::with(['project', 'assignee'])
        //     ->where('assigned_to', $user->id)
        //     ->get();
    }

    public function create(array $data): Task
    {
        // return Task::create($data)->fresh();
        $task = Task::with(['project', 'assignee'])->find(
            Task::create($data)->id
        );

        $this->clearCache();

        return $task;
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

        // Clear the cache
        $this->clearCache();

        return $task;
    }

    public function delete(Task $task)
    {
        // Clear cache before deleting
        $this->clearCache();
        $task->delete();
    }

    private function clearCache()
    {
        // Clear cache for all users since tasks are shared across roles
        User::pluck('id')->each(fn($id) => Cache::forget("tasks.user.{$id}"));
    }
}
