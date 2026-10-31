<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json(['status' => 'ok']);
    });

    Route::middleware('web')->group(function () {
        Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'login'])
            ->middleware('throttle:5,1');
    });

    Route::middleware(['auth:sanctum', \Illuminate\Session\Middleware\StartSession::class])->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\AuthController::class, 'me']);
    });

    Route::post('/auth/token', [\App\Http\Controllers\MobileAuthController::class, 'issueToken'])
        ->middleware(['throttle:5,1', \Illuminate\Session\Middleware\StartSession::class]);

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/auth/token', [\App\Http\Controllers\MobileAuthController::class, 'revokeToken']);
        Route::get('/me', [\App\Http\Controllers\MobileAuthController::class, 'me']);
    });

    Route::middleware(['auth:sanctum', \App\Http\Middleware\CurrentOrganizationMiddleware::class])
        ->prefix('org')
        ->group(function () {
            Route::get('/current', function (Request $request) {
                $organization = $request->get('current_organization');

                return response()->json([
                    'organization' => [
                        'id' => $organization->id,
                        'name' => $organization->name,
                        'slug' => $organization->slug,
                    ],
                ]);
            });
        });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
