<?php

namespace App\Enums;

enum FollowUpType: string
{
    case Call = 'call';
    case Message = 'message';
    case Email = 'email';
    case Meeting = 'meeting';
    case Task = 'task';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Call',
            self::Message => 'Message',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::Task => 'Task',
        };
    }
}
