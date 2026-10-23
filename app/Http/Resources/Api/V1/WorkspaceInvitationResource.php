<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WorkspaceInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkspaceInvitation
 */
class WorkspaceInvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => ['value' => $this->role->value, 'label' => $this->role->label()],
            'workspace' => $this->whenLoaded('workspace', fn () => ['id' => $this->workspace->id, 'name' => $this->workspace->name, 'type' => $this->workspace->type->label()]),
            'invited_by' => $this->inviter?->name,
            'expires_at' => $this->expires_at->toIso8601String(),
        ];
    }
}
