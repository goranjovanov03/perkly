<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;

class LogPushSender implements PushSender
{
    public function send(string $token, string $title, string $body, array $data): void
    {
        Log::info('Push notification sent', [
            'token' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}