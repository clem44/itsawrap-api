<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role_id !== 3) {
            return response()->json(['message' => 'Unauthorized. Customer access required.'], 403);
        }

        return $next($request);
    }
}
