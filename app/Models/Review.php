<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class Review extends Model
{
    protected $fillable = [
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'is_verified_purchase',
        'is_approved',
    ];

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('is_approved', true);
    }

    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->where('is_verified_purchase', true);
    }

    #[Scope]
    protected function rating(Builder $query, int $rating): void
    {
        $query->where('rating', $rating);
    }

        // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }   
}
