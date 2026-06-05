<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Cache;
use Illuminate\Database\Eloquent\Collection;
use Log;

class ProjectService
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

        $cacheKey = "projects.user.{$user->id}";

        try {
            $cachedIds = Cache::get($cacheKey);
            Log::info('Cache get result: ' . json_encode($cachedIds));

            if ($cachedIds) {
                return Project::with('owner')
                    ->whereIn('id', json_decode($cachedIds, true))
                    ->get();
            }

            $projects = $user->role === UserRole::Admin
                ? Project::with('owner')->get()
                : Project::with('owner')->where('user_id', $user->id)->get();

            $result = Cache::put($cacheKey, $projects->pluck('id')->toJson(), now()->addMinutes(10));
            Log::info('Cache put result: ' . json_encode($result));

            return $projects;

        } catch (\Exception $e) {
            Log::error('Cache error: ' . $e->getMessage());

            return $user->role === UserRole::Admin
                ? Project::with('owner')->get()
                : Project::with('owner')->where('user_id', $user->id)->get();
        }

        // Caching key (This failed because Redis cannot receive raw collections)
        // $cacheKey = "projects.user{user->id}";

        // // Admin Sees all projects, but Managers only see theirs, then it caches to reduce load time
        // return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
        //     if ($user->role === UserRole::Admin) {
        //         return Project::with('owner')->get();
        //     }

        //     return Project::with('owner')
        //         ->where('user_id', $user->id)
        //         ->get();
        // });

        // Admin sees all, Manager sees only their own (Old approach without caching)
        // if ($user->role === UserRole::Admin) {
        //     return Project::with('owner')->get();
        // }
    }

    public function create(User $user, array $data): Project
    {
        $project = Project::create([
            ...$data,
            'user_id' => $user->id,
        ])->fresh();

        // Clear the cache after creating
        $this->clearCache($user);

        return $project;
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project->fresh();
    }

    public function delete(Project $project)
    {
        // Clears cache
        $this->clearCache($project->owner);

        // Deletes project
        $project->delete();
    }

    private function clearCache(User $user): void
    {
        Cache::forget("projects.user.{$user->id}");

        // Also clear admin cache since admin sees all projects
        $adminCacheKeys = User::where('role', UserRole::Admin->value)
            ->pluck('id')
            ->each(fn($id) => Cache::forget("projects.user.{$id}"));
    }
}
