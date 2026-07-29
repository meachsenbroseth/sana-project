<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInteraction extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'customer_id',
        'session_id',
        'product_id',
        'event_type',
        'weight',
        'created_at',
    ];

    protected $casts = [
        'weight' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
