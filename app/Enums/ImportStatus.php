<?php

declare(strict_types=1);

namespace App\Enums;

/** Where an import is: waiting for its columns, being checked, ready to confirm, running, or done. */
enum ImportStatus: string
{
    case Mapping = 'mapping';
    case Checking = 'checking';
    case Ready = 'ready';
    case Importing = 'importing';
    case Completed = 'completed';
    case Failed = 'failed';

    /** Still working in the background, so the app keeps checking on it. */
    public function isBusy(): bool
    {
        return in_array($this, [self::Checking, self::Importing], true);
    }
}
