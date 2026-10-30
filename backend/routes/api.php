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
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
