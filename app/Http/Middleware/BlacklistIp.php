<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlacklistIp
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */

    // In a real app this would come from the database or cache
    protected array $blacklist = [
        '192.168.1.151',
        '10.0.0.5',
    ];
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->ip(), $this->blacklist)) {
            return response()->json([
                'message' => 'Access denied.'
            ], 403);
        }

        return $next($request);
    }
}
