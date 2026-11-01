<?php

namespace App\Services\Notifications;

interface EmailSender
{
    public function send(string $email, string $subject, string $html, array $data = []): void;
}