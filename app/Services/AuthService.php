<?php

namespace App\Services;

use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    public function register(array $data): array
    {
        // Creating user object
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role']
        ]);

        // Generating token
        $token = $user->createToken('auth_token')->plainTextToken;

        // returning both user object and token
        return ['user' => $user, 'token' => $token];
    }

    public function login(array $data): string
    {
        // Finding the user
        $user = User::where('email', $data['email'])->first();

        // Comparing them together
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['credentials do not match our records, Try again']
            ]);
        }

        // Creating the auth token
        return $user->createToken('auth_token')->plainTextToken;
    }

    public function logout($request)
    {
        $request->user()->currentAccessToken()->delete();
    }

    // To get the current user
    public function me(Request $request): User
    {
        return $request->user();
    }
}
