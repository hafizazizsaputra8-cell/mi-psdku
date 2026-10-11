<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CartItem extends Model        
{
    protected $table = 'cart_items';
    protected $fillable = ['user_id', 'product_id', 'quantity'];

    public function scopeByUserId(Builder $query, $userId)
    {
        return $query->where('user_id', $userId)->latest();
    }
}
