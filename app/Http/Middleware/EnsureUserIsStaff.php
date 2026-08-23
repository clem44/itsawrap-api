<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->user()?->role_id, [1, 2], true)) {
            return response()->json(['message' => 'Unauthorized. Staff access required.'], 403);
        }

        return $next($request);
    }
}
