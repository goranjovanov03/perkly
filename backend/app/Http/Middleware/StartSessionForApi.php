<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class StartSessionForApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            $request->setLaravelSession(
                app('session')->driver(Config::get('session.driver'))
            );
        }

        return $next($request);
    }
}