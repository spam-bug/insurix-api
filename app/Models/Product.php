<?php

namespace App\Models;

use App\Enums\PaymentFrequency;
use App\Enums\PolicyType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An insurer's product the agent sells, with the agent's usual commission rate for it.
 */
#[Fillable(['user_id', 'insurer', 'name', 'type', 'description', 'commission_rate', 'payment_frequency', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Policy, $this>
     */
    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => PolicyType::class,
            'payment_frequency' => PaymentFrequency::class,
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
