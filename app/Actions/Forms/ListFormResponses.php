<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\DataTransferObject\Forms\FormResponseFiltersData;
use App\Models\FormResponse;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ListFormResponses
{
    public const PER_PAGE = 25;

    /**
     * Newest first, with the form and the lead or client each became.
     *
     * @return LengthAwarePaginator<int, FormResponse>
     */
    public function handle(Workspace $workspace, FormResponseFiltersData $filters): LengthAwarePaginator
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("form-responses:{$workspace->id}:index:".md5(json_encode($filters)), now()->addMinutes(5), fn () => $workspace->formResponses()
            ->with(['form:id,name', 'lead:id,name', 'client:id,name'])
            ->when($filters->formId, fn ($query, string $formId) => $query->where('form_id', $formId))
            ->when($filters->leadId, fn ($query, string $leadId) => $query->where('lead_id', $leadId))
            ->when($filters->clientId, fn ($query, string $clientId) => $query->where('client_id', $clientId))
            ->when($filters->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(self::PER_PAGE, page: $filters->page));
    }

    /**
     * How many responses of the form are in each status, for the tabs.
     *
     * @return array<string, int>
     */
    public function counts(Workspace $workspace, string $formId): array
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("form-responses:{$workspace->id}:counts:{$formId}", now()->addMinutes(5), fn () => $workspace->formResponses()
            ->where('form_id', $formId)
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (mixed $total) => (int) $total)
            ->all());
    }
}
