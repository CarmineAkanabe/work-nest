<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Under the assumption the $request->user() might be null if authenticated, then we use this, but this is just a failsafe if sanctum fails
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $userRole = $request->user()->role->value;

        // If the Role of the current auth user ($request->user()->role) is not in the array
        if (!in_array($userRole, $roles)) {
            return response()->json([
                'message' => 'Unauthorised. Insufficient Role'
            ], 403);
        }

        return $next($request);
    }
}
