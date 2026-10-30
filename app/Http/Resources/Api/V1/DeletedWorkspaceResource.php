<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Workspace
 */
class DeletedWorkspaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'deleted_at' => $this->deleted_at->toIso8601String(),
            'restorable_until' => $this->deleted_at->copy()->addDays(Workspace::DELETION_GRACE_DAYS)->toIso8601String(),
        ];
    }
}
