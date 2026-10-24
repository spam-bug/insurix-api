<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Actions\FollowUps\CreateFollowUp;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\DataTransferObject\Policies\BulkPolicyFollowUpData;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BulkCreatePolicyFollowUps
{
    public function __construct(private CreateFollowUp $createFollowUp) {}

    /**
     * Adds the same follow-up for each selected policy, on its client and linked to the policy.
     * Ids that aren't this workspace's policies count as skipped.
     *
     * @return array{created: int, skipped: int}
     */
    public function handle(Workspace $workspace, BulkPolicyFollowUpData $data): array
    {
        $followUp = new FollowUpData(type: $data->type, title: $data->title, dueAt: $data->dueAt, notes: $data->notes);

        $created = DB::transaction(function () use ($workspace, $data, $followUp) {
            $policies = $workspace->policies()
                ->with('client.lead')
                ->whereKey($data->ids)
                ->get();

            foreach ($policies as $policy) {
                $this->createFollowUp->handle($policy->client, $followUp, $policy);
            }

            return $policies->count();
        });

        Cache::tags("workspace:{$workspace->id}")->flush();

        return ['created' => $created, 'skipped' => count($data->ids) - $created];
    }
}
