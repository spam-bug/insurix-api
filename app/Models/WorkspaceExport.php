<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A zip of the workspace's records as CSV files, built in the background. */
#[Fillable(['workspace_id', 'requested_by', 'status', 'path', 'completed_at'])]
class WorkspaceExport extends Model
{
    use HasUuids;

    /** Exports hold clients' personal details, so they're kept off the public disk. */
    public const DISK = 'local';

    /** How long a finished export stays downloadable. */
    public const KEEP_DAYS = 7;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ExportStatus::Processing->value,
    ];

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExportStatus::class,
            'completed_at' => 'datetime',
        ];
    }
}
