<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\User;

class MeasureLeadSources
{
    /** A source needs this many leads before its rate says much. */
    public const MIN_LEADS = 3;

    /**
     * "Which channels actually produce clients?": per source, the leads that came in, how many
     * became clients or were lost, and the conversion rate (clients over leads, in percent).
     * Sources with no leads are left out. Sources with enough leads come first, best rate first.
     *
     * @return array<int, array{source: string, label: string, leads: int, clients: int, lost: int, rate_percent: int, enough_data: bool}>
     */
    public function handle(User $agent): array
    {
        return $agent->leads()
            ->toBase()
            ->selectRaw('source, count(*) as leads')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as clients', [LeadStatus::Converted->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as lost', [LeadStatus::Lost->value])
            ->groupBy('source')
            ->get()
            ->map(function (object $row) {
                $source = LeadSource::from($row->source);
                $leads = (int) $row->leads;
                $clients = (int) $row->clients;

                return [
                    'source' => $source->value,
                    'label' => $source->label(),
                    'leads' => $leads,
                    'clients' => $clients,
                    'lost' => (int) $row->lost,
                    'rate_percent' => (int) round($clients / $leads * 100),
                    'enough_data' => $leads >= self::MIN_LEADS,
                ];
            })
            ->sortBy([['enough_data', 'desc'], ['rate_percent', 'desc'], ['leads', 'desc']])
            ->values()
            ->all();
    }
}
