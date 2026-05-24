<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Admin and Managers can see all projects.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager]);
    }

    /**
     * Admin, owning Manager, or any Employee can view a single project.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin
            || $user->id  === $project->user_id
            || $user->role === UserRole::Employee;
    }

    /**
     * Only Managers can create projects.
     */
    public function create(User $user): bool
    {
        return $user->role  === UserRole::Manager;
    }

    /**
     * Only Admin or the owning Manager can update.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin
            || $user->id  === $project->user_id;
    }

    /**
     * Only Admin can delete any project, Manager can delete their own.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin
            || $user->id === $project->user_id;
    }

    /**
     * No User can restore a task.
     */
    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Only the Admin can force delete a task.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
