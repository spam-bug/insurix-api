<?php

declare(strict_types=1);

namespace App\Actions\Mentions;

use App\Models\Lead;
use App\Models\Mention;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SuggestMentions
{
    /**
     * Who the agent most likely wants to mention on this lead: teammates who worked on it most
     * recently, then the people the agent mentioned last. Never the agent.
     *
     * @return array<int, string> user ids, best first
     */
    public function handle(Lead $lead, User $user): array
    {
        return Cache::tags("workspace:{$lead->workspace_id}")->rememberForever("mentions:{$lead->workspace_id}:suggestions:{$lead->id}:{$user->id}", function () use ($lead, $user) {
            $workedOnLead = $lead->activities()
                ->whereNotNull('user_id')
                ->where('user_id', '!=', $user->id)
                ->groupBy('user_id')
                ->orderByRaw('max(created_at) desc')
                ->limit(3)
                ->pluck('user_id');

            $mentionedLately = Mention::where('workspace_id', $lead->workspace_id)
                ->where('mentioned_by', $user->id)
                ->groupBy('user_id')
                ->orderByRaw('max(created_at) desc')
                ->limit(3)
                ->pluck('user_id');

            return $workedOnLead->merge($mentionedLately)->unique()->take(5)->values()->all();
        });
    }
}
