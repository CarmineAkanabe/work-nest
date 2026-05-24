<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     *  Admin and Manager can see all tasks.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);;
    }

    /**
     * Everyone can view a single task.
     */
    public function view(User $user, Task $task): bool
    {
        return true;
    }

    /**
     * Only Managers can create a Task.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Manager;
    }

    /**
     * Admin, Manager, or the assigned Employee can update a task.
     */
    public function update(User $user, Task $task): bool
    {
        return $user->role === UserRole::Admin
            || $user->role === UserRole::Manager
            || $user->id === $task->assigned_to;
    }

    /**
     * Only Admin or a Manager can delete.
     */
    public function delete(User $user, Task $task): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }
}
