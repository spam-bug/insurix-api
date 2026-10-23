<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkspaceMember
 */
class WorkspaceMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => ['value' => $this->role->value, 'label' => $this->role->label()],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'avatar_url' => $this->user->avatarUrl(),
            ],
            'joined_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
