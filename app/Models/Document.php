<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use Carbon\CarbonImmutable;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file the agent keeps for a client, such as a policy contract or a valid ID.
 */
#[Fillable(['user_id', 'client_id', 'policy_id', 'category', 'name', 'original_name', 'path', 'mime_type', 'size', 'expires_on', 'notes'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /** Client documents hold personal details, so they're kept off the public disk. */
    public const DISK = 'local';

    /** A document expiring within this many days needs replacing soon. */
    public const EXPIRING_DAYS = 30;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * "expired", "expiring" within EXPIRING_DAYS, or null when it doesn't expire soon.
     */
    public function expiryStatus(?CarbonImmutable $today = null): ?string
    {
        if ($this->expires_on === null) {
            return null;
        }

        $today ??= CarbonImmutable::today();

        return match (true) {
            $this->expires_on->lessThan($today) => 'expired',
            $this->expires_on->lessThanOrEqualTo($today->addDays(self::EXPIRING_DAYS)) => 'expiring',
            default => null,
        };
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
            'expires_on' => 'immutable_date',
        ];
    }
}
