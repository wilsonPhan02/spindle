<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckProjectAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $request->route('project') could be a string if SubstituteBindings hasn't run yet
        $project = $request->route('project');

        if (is_string($project)) {
            $project = \App\Models\Project::find($project);
        }

        if ($project) {
            // 1. Check Ownership
            if ($project->user_id !== Auth::id()) {
                return redirect()->route('dashboard');
            }

            // 2. Check Archived Status
            if ($project->archived_at !== null) {
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
