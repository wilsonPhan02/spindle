<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckProjectAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $request->route('project') could be a string if SubstituteBindings hasn't run yet
        $project = $request->route('project');

        if (is_string($project)) {
            $project = Project::find($project);
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
