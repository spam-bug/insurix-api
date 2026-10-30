<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WorkspaceExport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkspaceExport
 */
class WorkspaceExportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'requested_by' => $this->whenLoaded('requester', fn () => $this->requester?->only('id', 'name')),
            'created_at' => $this->created_at->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'expires_at' => $this->created_at->copy()->addDays(WorkspaceExport::KEEP_DAYS)->toIso8601String(),
        ];
    }
}
