<?php

declare(strict_types=1);

namespace App\Enums;

enum ExportStatus: string
{
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Processing => 'Preparing',
            self::Ready => 'Ready',
            self::Failed => 'Failed',
        };
    }
}
