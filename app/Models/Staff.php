<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StaffRole;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/** Someone on the Suvvi team, signed in through the `staff` guard. */
#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class Staff extends Authenticatable
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory, HasUuids, TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => StaffRole::class,
            'two_factor_confirmed_at' => 'immutable_datetime',
        ];
    }
}
