<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadActivityType;
use Database\Factories\LeadActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'type', 'body', 'properties'])]
class LeadActivity extends Model
{
    /** @use HasFactory<LeadActivityFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The note this entry stands for, when it's a note.
     *
     * @return HasOne<Note, $this>
     */
    public function note(): HasOne
    {
        return $this->hasOne(Note::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => LeadActivityType::class,
            'properties' => 'array',
        ];
    }
}
