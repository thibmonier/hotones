<?php

namespace App\Enum;

enum NotificationChannel: string
{
    case IN_APP  = 'in_app';
    case EMAIL   = 'email';
    case WEBHOOK = 'webhook';

    public function getLabel(): string
    {
        return match ($this) {
            self::IN_APP  => 'Application',
            self::EMAIL   => 'Email',
            self::WEBHOOK => 'Webhook (Slack/Discord)',
        };
    }
}
