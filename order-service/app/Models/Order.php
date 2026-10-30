<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PAID = 'PAID';

    public const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
    ];

    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'total_amount' => 'integer',
            'created_at' => 'datetime:Y-m-d\TH:i:sP',
            'updated_at' => 'datetime:Y-m-d\TH:i:sP',
        ];
    }

    /**
     * Relationship: An order has many order items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Scope to filter orders by user ID.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter orders by status.
     */
    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        if ($status !== null && $status !== '') {
            $query->where('status', strtoupper($status));
        }

        return $query;
    }

    /**
     * Check if the order is still in PENDING status.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the order is already PAID.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
