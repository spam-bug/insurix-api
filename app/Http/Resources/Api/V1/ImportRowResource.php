<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\ImportRow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ImportRow
 */
class ImportRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_number' => $this->row_number,
            'status' => $this->status->value,
            'values' => (object) $this->values,
            'errors' => (object) ($this->errors ?? []),
            /** Suggested values for fields with problems, field => value. */
            'suggestions' => (object) ($this->suggestions ?? []),
            'duplicate' => $this->duplicate_id ? ['id' => $this->duplicate_id, 'name' => $this->duplicate_name] : null,
            'same_person_as' => $this->same_person_as,
        ];
    }
}
