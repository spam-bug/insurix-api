<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\ImportField;
use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Import
 */
class ImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'status' => $this->status->value,
            'busy' => $this->status->isBusy(),
            'original_name' => $this->original_name,
            'headers' => $this->headers,
            'sample' => $this->sample,
            'mapping' => (object) ($this->mapping ?? []),
            'fields' => array_map(fn (ImportField $field) => [
                'value' => $field->value,
                'label' => $field->label(),
                'policy' => $field->isPolicyField(),
            ], ImportField::for($this->kind)),
            'update_duplicates' => $this->update_duplicates,
            'counts' => [
                'total' => $this->total_rows,
                'valid' => $this->valid_rows,
                'invalid' => $this->invalid_rows,
                'duplicate' => $this->duplicate_rows,
                'imported' => $this->imported_rows,
                'fixable' => $this->rows()->whereNotNull('suggestions')->count(),
            ],
            'failure' => $this->failure,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
