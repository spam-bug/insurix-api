<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Documents\MeasureStorage;
use App\Models\Workspace;

class MeasureUsage
{
    public function __construct(private MeasureStorage $measureStorage) {}

    /**
     * How much of each plan limit the workspace uses. Seats count members and pending invites,
     * as inviting does; a null limit means no limit. Storage is in bytes.
     *
     * @return array{seats: array{used: int, limit: int}, forms: array{used: int, limit: ?int}, storage: array{used: int, limit: int}}
     */
    public function handle(Workspace $workspace): array
    {
        $plan = $workspace->currentPlan();
        $workspace->loadCount([
            'members',
            'forms',
            'invitations as pending_invitations_count' => fn ($query) => $query->whereNull('accepted_at')->where('expires_at', '>', now()),
        ]);

        return [
            'seats' => ['used' => $workspace->members_count + $workspace->pending_invitations_count, 'limit' => $plan->seatLimit()],
            'forms' => ['used' => $workspace->forms_count, 'limit' => $plan->formLimit()],
            'storage' => $this->measureStorage->handle($workspace),
        ];
    }
}
