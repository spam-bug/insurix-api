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

#[Fillable(['workspace_id', 'user_id', 'kind', 'status', 'original_name', 'path', 'headers', 'sample', 'mapping', 'fixes', 'responses', 'update_duplicates', 'total_rows', 'valid_rows', 'invalid_rows', 'duplicate_rows', 'imported_rows', 'failure'])]
class Import extends Model
{
    use HasUuids;

    /** Uploaded spreadsheets are private, like documents. */
    public const DISK = 'local';

    /** How many rows of the file the matching step shows. */
    public const SAMPLE_ROWS = 3;

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
            'fixes' => 'array',
            'responses' => 'array',
            'update_duplicates' => 'boolean',
        ];
    }
}
