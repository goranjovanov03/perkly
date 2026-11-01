<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;

class LogEmailSender implements EmailSender
{
    public function send(string $email, string $subject, string $html, array $data = []): void
    {
        Log::info('Email sent', [
            'email' => $email,
            'subject' => $subject,
            'html' => $html,
            'data' => $data,
        ]);
    }
}