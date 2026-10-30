<?php

declare(strict_types=1);

namespace App\Actions\Premium;

use App\DataTransferObject\Premium\PremiumLinkData;
use App\Models\PremiumLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePremiumLink
{
    public function handle(PremiumLinkData $data, ?User $creator = null): PremiumLink
    {
        return DB::transaction(fn () => PremiumLink::create([
            ...$data->toArray(),
            'token' => Str::random(40),
            'created_by' => $creator?->id,
        ]));
    }
}
