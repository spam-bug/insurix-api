<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Sanctum's API token, keyed by UUID like everything else. The plain-text token is still
 * "{id}|{secret}", so Sanctum finds it by its UUID.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;
}
