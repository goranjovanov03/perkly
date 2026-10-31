<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class AuthenticateApi extends Middleware
{
    protected function redirectTo($request): ?string
    {
        if ($request->is('api/*')) {
            return null;
        }

        return parent::redirectTo($request);
    }

    protected function unauthenticated($request, array $guards)
    {
        if ($request->is('api/*')) {
            throw new AuthenticationException(
                'Unauthenticated.',
                $guards,
                null
            );
        }

        parent::unauthenticated($request, $guards);
    }

    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = ['sanctum'];
        }

        parent::authenticate($request, $guards);
    }
}