<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportRowStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['import_id', 'row_number', 'status', 'values', 'data', 'errors', 'duplicate_id', 'duplicate_name', 'same_person_as'])]
class ImportRow extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Import, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportRowStatus::class,
            'values' => 'array',
            'data' => 'array',
            'errors' => 'array',
        ];
    }
}
