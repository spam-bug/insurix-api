<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/** Reads --days, --years or --forever from a premium command: days, null for forever, or false when unclear. */
class PremiumDuration
{
    public static function days(Command $command): int|false|null
    {
        $chosen = array_filter([$command->option('days'), $command->option('years'), $command->option('forever')]);

        if (count($chosen) !== 1) {
            $command->error('Pass exactly one of --days, --years or --forever.');

            return false;
        }
        if ($command->option('forever')) {
            return null;
        }

        return $command->option('years') ? (int) $command->option('years') * 365 : (int) $command->option('days');
    }
}
