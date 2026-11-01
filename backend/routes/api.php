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
        Route::patch('/me/locale', [\App\Http\Controllers\AuthController::class, 'updateLocale']);

        Route::post('/me/devices', [\App\Http\Controllers\Api\V1\DeviceInstallationController::class, 'store']);
        Route::delete('/me/devices/{id}', [\App\Http\Controllers\Api\V1\DeviceInstallationController::class, 'destroy']);
    });

    Route::middleware([\App\Http\Middleware\AuthenticateApi::class, \Illuminate\Session\Middleware\StartSession::class, \App\Http\Middleware\CurrentOrganizationMiddleware::class])->group(function () {
        Route::get('/me/notification-preferences', [\App\Http\Controllers\Api\V1\NotificationPreferenceController::class, 'index']);
        Route::put('/me/notification-preferences', [\App\Http\Controllers\Api\V1\NotificationPreferenceController::class, 'update']);
    });

    Route::middleware([\App\Http\Middleware\AuthenticateApi::class])->group(function () {
        Route::get('/bootstrap', [\App\Http\Controllers\AuthController::class, 'bootstrap']);
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
            Route::post('/files', [\App\Http\Controllers\Api\V1\FileController::class, 'store']);
            Route::get('/files', [\App\Http\Controllers\Api\V1\FileController::class, 'index']);
            Route::get('/files/{file}', [\App\Http\Controllers\Api\V1\FileController::class, 'show']);

            Route::get('/notifications', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
            Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead']);
            Route::post('/notifications/read-all', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAllAsRead']);

            Route::get('/reference/countries', [\App\Http\Controllers\Api\V1\ReferenceController::class, 'countries']);
            Route::get('/reference/currencies', [\App\Http\Controllers\Api\V1\ReferenceController::class, 'currencies']);
            Route::get('/reference/locales', [\App\Http\Controllers\Api\V1\ReferenceController::class, 'locales']);

            Route::get('/integrations', [\App\Http\Controllers\Api\V1\IntegrationController::class, 'index']);
            Route::post('/integrations', [\App\Http\Controllers\Api\V1\IntegrationController::class, 'store']);
            Route::get('/integrations/{id}', [\App\Http\Controllers\Api\V1\IntegrationController::class, 'show']);
            Route::put('/integrations/{id}/secrets', [\App\Http\Controllers\Api\V1\IntegrationController::class, 'updateSecrets']);
            Route::post('/integrations/{id}/test', [\App\Http\Controllers\Api\V1\IntegrationController::class, 'test']);
            Route::put('/integrations/{id}/config', [\App\Http\Controllers\Api\V1\IntegrationController::class, 'updateConfig']);
        });
});

Route::middleware([\App\Http\Middleware\AuthenticateApi::class])
    ->prefix('files')
    ->group(function () {
        Route::get('/download/{file}', [\App\Http\Controllers\Api\V1\FileController::class, 'download'])
            ->name('files.download')
            ->middleware('signed');
    });

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');