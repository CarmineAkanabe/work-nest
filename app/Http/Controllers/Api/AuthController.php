<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // We use a constructor of the AuthService so as to maintain that iinstance, n manage memory
    public function __construct(private AuthService $authService)   {}
    public function login(LoginRequest $request)
    {
        $validatedData = $request->validated();

        // we pass this value to the login method in AuthService
        $token = $this->authService->login($validatedData);

        return response()->json(['token' => $token]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        // Only Admin can create accounts
        $this->authorize('is_admin');

        $result = $this->authService->register($request->validated());

        return response()->json([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 201);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request);

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request);

        return response()->json(new UserResource($user));
    }
}
