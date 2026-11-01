<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Push = 'push';
    case Email = 'email';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}