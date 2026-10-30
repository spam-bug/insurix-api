<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FormStatus;
use App\Enums\FormTemplate;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A shareable form: the builder edits `draft`, visitors see `published`. */
#[Fillable(['name', 'token', 'status', 'is_default', 'template', 'draft', 'published', 'published_at'])]
class Form extends Model
{
    use HasUuids;

    /**
     * Every field of the published form, in order, without their sections.
     *
     * @return list<array<string, mixed>>
     */
    public function publishedFields(): array
    {
        return array_merge(...array_column($this->published['sections'] ?? [], 'fields'));
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<FormResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FormStatus::class,
            'is_default' => 'boolean',
            'template' => FormTemplate::class,
            'draft' => 'array',
            'published' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
