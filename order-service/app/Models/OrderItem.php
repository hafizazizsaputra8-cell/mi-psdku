<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    /**
     * Attributes hidden from serialization to match exact API response specification.
     */
    protected $hidden = [
        'id',
        'order_id',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'order_id' => 'integer',
            'product_id' => 'integer',
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    /**
     * Relationship: An order item belongs to an order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
