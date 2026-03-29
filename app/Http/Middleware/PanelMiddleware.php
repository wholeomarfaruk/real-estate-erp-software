<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PanelMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $panel): Response
    {

        // Check if user is logged in AND has access to panel
        if (!auth()->check() || !auth()->user()->hasPanel($panel) || !auth()->user()->roleName()) {
            // Option: Redirect to a specific page or show 403
            abort(403, "Access Denied: You do not have the {$panel} panel permissions.");
        }

        return $next($request);
    }
}
