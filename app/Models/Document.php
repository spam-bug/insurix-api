<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentCategory;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file the agent keeps for a client, such as a policy contract or a valid ID.
 */
#[Fillable(['workspace_id', 'client_id', 'policy_id', 'category', 'name', 'original_name', 'path', 'mime_type', 'size', 'notes'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, HasUuids;

    /** Client documents hold personal details, so they're kept off the public disk. */
    public const DISK = 'local';

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The policy the document belongs to, if any.
     *
     * @return BelongsTo<Policy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * The name to save the file as: the document's name with the uploaded file's extension.
     */
    public function downloadName(): string
    {
        $extension = pathinfo($this->original_name, PATHINFO_EXTENSION);

        return $extension === '' || str_ends_with(strtolower($this->name), '.'.strtolower($extension))
            ? $this->name
            : "{$this->name}.{$extension}";
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'size' => 'integer',
        ];
    }
}
