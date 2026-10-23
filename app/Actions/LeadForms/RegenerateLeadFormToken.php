<?php

declare(strict_types=1);

namespace App\Actions\LeadForms;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegenerateLeadFormToken
{
    /**
     * Issues a new token, so any previously shared link stops working.
     */
    public function handle(User $agent): string
    {
        DB::transaction(fn () => $agent->forceFill(['lead_form_token' => Str::random(40)])->save());

        return $agent->lead_form_token;
    }
}
