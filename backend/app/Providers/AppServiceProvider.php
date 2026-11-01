<?php

namespace App\Providers;

use App\Services\Integrations\FakeIntegrationTestProvider;
use App\Services\Integrations\IntegrationTestProvider;
use App\Services\Notifications\EmailSender;
use App\Services\Notifications\LogEmailSender;
use App\Services\Notifications\LogPushSender;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\PushSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PushSender::class, LogPushSender::class);
        $this->app->bind(EmailSender::class, LogEmailSender::class);
        $this->app->singleton(NotificationDispatcher::class);

        if ($this->app->environment('testing')) {
            $this->app->bind(IntegrationTestProvider::class, FakeIntegrationTestProvider::class);
        }
    }

    public function boot(): void {}
}