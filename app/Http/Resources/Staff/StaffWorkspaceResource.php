<?php

declare(strict_types=1);

namespace App\Http\Resources\Staff;

use App\Enums\Plan;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A workspace as the staff console lists it: its plan as it stands today, its owner and size.
 *
 * @mixin Workspace
 */
class StaffWorkspaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $plan = $this->currentPlan();
        $owner = $this->users->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'plan' => ['value' => $plan->value, 'label' => $plan->label()],
            'plan_ends_at' => $plan === Plan::Premium ? $this->plan_ends_at?->toIso8601String() : null,
            'owner' => $owner ? ['id' => $owner->id, 'name' => $owner->name, 'email' => $owner->email] : null,
            'members_count' => $this->members_count,
            'leads_count' => $this->leads_count,
            'clients_count' => $this->clients_count,
            'created_at' => $this->created_at->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'purged_at' => $this->deleted_at?->addDays(Workspace::DELETION_GRACE_DAYS)->toIso8601String(),
        ];
    }
}
