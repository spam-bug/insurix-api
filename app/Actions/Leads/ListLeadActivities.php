<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

class ListLeadActivities
{
    /**
     * @return LengthAwarePaginator<int, LeadActivity>
     */
    public function handle(Lead $lead, int $perPage = 20): LengthAwarePaginator
    {
        return Cache::tags("workspace:{$lead->workspace_id}")->rememberForever("leads:{$lead->workspace_id}:{$lead->id}:activities:page-".Paginator::resolveCurrentPage()."-{$perPage}", fn () => $lead->activities()
            ->with(['user:id,name', 'note:id,lead_activity_id,body'])
            ->latest()
            ->latest('id')
            ->paginate($perPage));
    }
}
