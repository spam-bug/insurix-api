<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something the insurer still needs before it decides on an application, such as a medical
 * exam or a valid ID. Received once the agent ticks it off, optionally with the document.
 */
#[Fillable(['title', 'document_id', 'received_on'])]
class ApplicationRequirement extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Policy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * The uploaded document that covers this requirement, if any.
     *
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'received_on' => 'immutable_date',
        ];
    }
}
