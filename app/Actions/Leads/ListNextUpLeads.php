<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Builders\LeadBuilder;
use App\Enums\LeadActivityType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ListNextUpLeads
{
    public const LIMIT = 5;

    /** A hot lead counts as waiting after this many days without contact. */
    public const HOT_QUIET_DAYS = 3;

    /**
     * The few open leads that need the agent now, each with why: an overdue follow-up first, then
     * hot leads gone quiet, then form replies nobody has contacted. Leads with a follow-up planned
     * only qualify when it's overdue. Cached briefly since "overdue" moves with the clock.
     *
     * @return Collection<int, array{lead: Lead, reason: string}>
     */
    public function handle(Workspace $workspace): Collection
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("leads:{$workspace->id}:next-up", now()->addMinutes(5), function () use ($workspace) {
            $open = fn (): LeadBuilder => $workspace->leads()->getQuery()->whereIn('status', LeadStatus::open())->with('client:id,lead_id')->withNextStep()->limit(self::LIMIT);
            $nothingPlanned = fn (Builder $query) => $query->whereDoesntHave('followUps', fn (Builder $followUps) => $followUps->whereNull('completed_at'));

            $overdue = $open()
                ->whereHas('followUps', fn (Builder $query) => $query->whereNull('completed_at')->where('due_at', '<', now()))
                ->orderBy('next_follow_up_at')
                ->get();

            $quietHot = $open()
                ->where('score', '>=', ScoreLead::HOT_SCORE)
                ->where($nothingPlanned)
                ->where(fn (Builder $query) => $query
                    ->whereDoesntHave('activities', fn (Builder $activities) => $activities->whereIn('type', LeadActivityType::contacts())->where('created_at', '>=', now()->subDays(self::HOT_QUIET_DAYS)))
                    ->where('created_at', '<', now()->subDays(self::HOT_QUIET_DAYS)))
                ->orderByDesc('score')
                ->get();

            $formReplies = $open()
                ->where('source', LeadSource::ShareableForm)
                ->where($nothingPlanned)
                ->whereDoesntHave('activities', fn (Builder $activities) => $activities->whereIn('type', LeadActivityType::contacts()))
                ->latest()
                ->get();

            return collect([
                ...$overdue->map(fn (Lead $lead) => ['lead' => $lead, 'reason' => 'overdue']),
                ...$quietHot->map(fn (Lead $lead) => ['lead' => $lead, 'reason' => 'hot_quiet']),
                ...$formReplies->map(fn (Lead $lead) => ['lead' => $lead, 'reason' => 'form_reply']),
            ])->unique(fn (array $item) => $item['lead']->id)->take(self::LIMIT)->values();
        });
    }
}
