<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeveloper
{
    /**
     * Allow only the hidden developer account through. Everyone else — admin
     * or normal user — gets a 404 so the panel stays invisible.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isDeveloper(), 404);

        return $next($request);
    }
}
