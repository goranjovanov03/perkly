<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;
use Illuminate\Http\Request;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            $requestId = $request->header('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid();

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => 'Unauthenticated.',
                        'request_id' => $requestId,
                    ],
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => 'Forbidden.',
                        'request_id' => $requestId,
                    ],
                ], 403);
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => $e->getMessage(),
                        'request_id' => $requestId,
                    ],
                ], 422);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Not found.',
                        'request_id' => $requestId,
                    ],
                ], 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'error' => [
                        'code' => 'HTTP_ERROR',
                        'message' => $e->getMessage(),
                        'request_id' => $requestId,
                    ],
                ], $e->getStatusCode());
            }

            return response()->json([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Internal server error.',
                    'request_id' => $requestId,
                ],
            ], 500);
        });
    }
}