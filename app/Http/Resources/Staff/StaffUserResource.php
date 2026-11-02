<?php

declare(strict_types=1);

namespace App\Http\Resources\Staff;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An agent as the staff console lists them, with the workspaces they're in.
 *
 * @mixin User
 */
class StaffUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at !== null,
            'two_factor' => $this->two_factor_confirmed_at !== null,
            'workspaces' => $this->workspaces->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'role' => ['value' => $workspace->membership->role->value, 'label' => $workspace->membership->role->label()],
            ]),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
