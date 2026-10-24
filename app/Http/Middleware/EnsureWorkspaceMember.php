<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceMember
{
    /**
     * Lets members through to their workspace's routes; anyone else gets a 404, so workspace ids
     * don't leak. The workspace is shared through the container and dropped from the route
     * parameters, so controllers and form requests ask for it by type and methods that don't
     * need it aren't handed it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $request->route('workspace');

        abort_unless($workspace instanceof Workspace && $request->user()?->can('view', $workspace), 404);

        app()->instance(Workspace::class, $workspace);
        $request->route()->forgetParameter('workspace');

        return $next($request);
    }
}
