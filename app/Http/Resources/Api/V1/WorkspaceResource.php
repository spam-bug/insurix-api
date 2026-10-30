<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\Plan;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Pennant\Feature;

/**
 * @mixin Workspace
 */
class WorkspaceResource extends JsonResource
{
    /**
     * The signed-in user's role is there when the workspace came through their membership.
     * Features lists only the ones this workspace may use.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'timezone' => $this->timezone,
            'logo_url' => $this->logoUrl(),
            'plan' => ['value' => $this->currentPlan()->value, 'label' => $this->currentPlan()->label()],
            'plan_ends_at' => $this->currentPlan() === Plan::Premium ? $this->plan_ends_at?->toIso8601String() : null,
            'features' => array_keys(array_filter(Feature::for($this->resource)->all())),
            'role' => $this->whenPivotLoadedAs('membership', 'workspace_members', fn () => [
                'value' => $this->membership->role->value,
                'label' => $this->membership->role->label(),
            ]),
            'members_count' => $this->whenCounted('members'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
