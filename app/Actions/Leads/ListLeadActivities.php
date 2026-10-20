<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListLeadActivities
{
    /**
     * @return LengthAwarePaginator<int, LeadActivity>
     */
    public function handle(Lead $lead, int $perPage = 20): LengthAwarePaginator
    {
        return $lead->activities()
            ->with('user:id,name')
            ->latest()
            ->latest('id')
            ->paginate($perPage);
    }
}
