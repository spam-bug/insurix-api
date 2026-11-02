<?php

declare(strict_types=1);

namespace App\Http\Resources\Staff;

use App\Models\PremiumGrant;
use App\Models\StaffActivity;
use App\Models\Workspace;
use Illuminate\Http\Request;

/**
 * A workspace's page in the staff console: everyone in it, its Premium history and what staff
 * changed on it.
 *
 * @mixin Workspace
 */
class StaffWorkspaceDetailResource extends StaffWorkspaceResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'members' => $this->members->map(fn ($member) => [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'role' => ['value' => $member->role->value, 'label' => $member->role->label()],
            ]),
            'grants' => $this->premiumGrants->map(fn (PremiumGrant $grant) => [
                'id' => $grant->id,
                'days' => $grant->days,
                'plan_ends_at' => $grant->plan_ends_at?->toIso8601String(),
                'note' => $grant->note,
                'by_link' => $grant->premium_link_id !== null,
                'created_at' => $grant->created_at->toIso8601String(),
            ]),
            'activities' => $this->staffActivities->map(fn (StaffActivity $activity) => [
                'id' => $activity->id,
                'type' => ['value' => $activity->type->value, 'label' => $activity->type->label()],
                'staff_name' => $activity->staff->name ?? 'Former staff',
                'properties' => $activity->properties,
                'created_at' => $activity->created_at->toIso8601String(),
            ]),
        ];
    }
}
