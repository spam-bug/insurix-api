<?php

declare(strict_types=1);

namespace App\Actions\LeadForms;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegenerateLeadFormToken
{
    /**
     * Issues a new token, so any previously shared link stops working.
     */
    public function handle(Workspace $workspace): string
    {
        DB::transaction(fn () => $workspace->forceFill(['lead_form_token' => Str::random(40)])->save());

        return $workspace->lead_form_token;
    }
}
