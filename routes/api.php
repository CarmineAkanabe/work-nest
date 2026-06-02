-- Active: 1778161058611@@127.0.0.1@5432@work_nest
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Middleware Routes (Sanctum)
Route::middleware('auth:sanctum')->group(function (){
    //
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Admin Only Routes (Protected with RoleMiddleware, and role 'admin' is sent to string ..$roles)
    Route::middleware('role:admin')->group(function (){
        Route::post('/register', [AuthController::class, 'register']);
    });

    // Admin and Manager Routes (Protected with RoleMiddleware, and role 'admin'and 'manager' to string ..$roles)
    Route::middleware('role:admin,manager')->group(function () {
        Route::apiResource('projects', ProjectController::class);
        Route::apiResource('tasks', TaskController::class);
    });

    // // Project Routes
    // Route::apiResource('projects', ProjectController::class);

    // // Task Routes
    // Route::apiResource('tasks', TaskController::class);

});
