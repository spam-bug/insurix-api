<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Workspace
 */
class WorkspaceResource extends JsonResource
{
    /**
     * The signed-in user's role is there when the workspace came through their membership.
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
            'bio' => $this->bio,
            'logo_url' => $this->logoUrl(),
            'role' => $this->whenPivotLoadedAs('membership', 'workspace_members', fn () => [
                'value' => $this->membership->role->value,
                'label' => $this->membership->role->label(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
