<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isClient()) {
            return response()->json([
                'success' => false,
                'message' => 'This account is not authorized to use the client app.',
            ], 403);
        }

        return $next($request);
    }
}
