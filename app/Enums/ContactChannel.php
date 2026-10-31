<?php

declare(strict_types=1);

namespace App\Enums;

/** How a lead or client prefers to be reached; the agent's own app opens the chat. */
enum ContactChannel: string
{
    case Call = 'call';
    case Sms = 'sms';
    case Email = 'email';
    case Viber = 'viber';
    case WhatsApp = 'whatsapp';
    case Messenger = 'messenger';
    case Telegram = 'telegram';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Call',
            self::Sms => 'SMS',
            self::Email => 'Email',
            self::Viber => 'Viber',
            self::WhatsApp => 'WhatsApp',
            self::Messenger => 'Messenger',
            self::Telegram => 'Telegram',
        };
    }
}
