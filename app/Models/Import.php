<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportKind;
use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'user_id', 'kind', 'status', 'original_name', 'path', 'headers', 'sample', 'mapping', 'update_duplicates', 'total_rows', 'valid_rows', 'invalid_rows', 'duplicate_rows', 'imported_rows', 'failure'])]
class Import extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Who uploaded the file.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ImportRow, $this>
     */
    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ImportKind::class,
            'status' => ImportStatus::class,
            'headers' => 'array',
            'sample' => 'array',
            'mapping' => 'array',
            'update_duplicates' => 'boolean',
        ];
    }
}
