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
        Route::post('/auth/forgot-password', [\App\Http\Controllers\AuthController::class, 'forgotPassword'])
            ->middleware('throttle:3,15');
        Route::post('/auth/reset-password', [\App\Http\Controllers\AuthController::class, 'resetPassword']);
    });

    Route::middleware([\App\Http\Middleware\AuthenticateApi::class, \Illuminate\Session\Middleware\StartSession::class])->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
        Route::post('/auth/logout-all', [\App\Http\Controllers\AuthController::class, 'logoutAll']);
        Route::get('/me', [\App\Http\Controllers\AuthController::class, 'me']);
        Route::get('/me/organizations', [\App\Http\Controllers\AuthController::class, 'organizations']);
        Route::post('/me/onboarding/complete', [\App\Http\Controllers\AuthController::class, 'completeOnboarding']);
        Route::patch('/me/password', [\App\Http\Controllers\AuthController::class, 'updatePassword']);
    });

    Route::post('/auth/token', [\App\Http\Controllers\MobileAuthController::class, 'issueToken'])
        ->middleware(['throttle:5,1', \Illuminate\Session\Middleware\StartSession::class]);

    Route::middleware(\App\Http\Middleware\AuthenticateApi::class)->group(function () {
        Route::delete('/auth/token', [\App\Http\Controllers\MobileAuthController::class, 'revokeToken']);
    });

    Route::middleware([\App\Http\Middleware\AuthenticateApi::class, \App\Http\Middleware\CurrentOrganizationMiddleware::class])
        ->prefix('org')
        ->group(function () {
            Route::get('/current', [\App\Http\Controllers\Api\V1\OrganizationController::class, 'show']);
            Route::patch('/current', [\App\Http\Controllers\Api\V1\OrganizationController::class, 'update']);
            Route::get('/members', [\App\Http\Controllers\Api\V1\OrganizationController::class, 'members']);
        });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
