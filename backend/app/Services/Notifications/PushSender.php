<?php

namespace App\Services\Notifications;

interface PushSender
{
    public function send(string $token, string $title, string $body, array $data): void;
}