<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSimilarity extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'related_id',
        'score',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_id');
    }
}
