<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{

    public function __construct(private ProjectService $projectService) {}

    /**
     * You can use
     * return new ProjectResource($project); if the return type is JsonResponse
     */

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);
        $projects = $this->projectService->getAll($request->user());
        return response()->json(ProjectResource::collection($projects));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);
        $project = $this->projectService->create($request->user(), $request->validated());
        return response()->json(new ProjectResource($project), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        return response()->json(new ProjectResource($project));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);
        $project = $this->projectService->update($project, $request->validated());
        return response()->json(new ProjectResource($project));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);
        $this->projectService->delete($project);
        return response()->json(['message' => 'Project deleted.']);
    }
}
