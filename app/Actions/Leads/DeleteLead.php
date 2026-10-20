<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Models\Lead;

class DeleteLead
{
    public function handle(Lead $lead): void
    {
        $lead->delete();
    }
}
