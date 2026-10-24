<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\ProductBuilder;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;

/**
 * A product the agent sells, with the agent's usual commission rate for it.
 */
#[Fillable(['workspace_id', 'name', 'type', 'description', 'commission_rate', 'payment_frequency', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUuids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<Policy, $this>
     */
    /**
     * @param  Builder  $query
     */
    public function newEloquentBuilder($query): ProductBuilder
    {
        return new ProductBuilder($query);
    }

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
