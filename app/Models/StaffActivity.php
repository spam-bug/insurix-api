<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StaffActivityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One change a staff member made in the console, kept for the audit log. */
#[Fillable(['staff_id', 'type', 'subject_type', 'subject_id', 'properties'])]
class StaffActivity extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StaffActivityType::class,
            'properties' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
