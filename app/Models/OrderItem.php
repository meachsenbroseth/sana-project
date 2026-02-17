<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_amount',
        'total_amount',
    ];
    protected $casts = [
        'quantity' => 'integer',
        'unit_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function order(){
        return $this->belongsTo(Order::class);
    }
    public function product(){
        return $this->belongsTo(Product::class);
    }
}
