<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    #[Scope]
    protected function primary(Builder $query): void 
    {
        $query->where('is_primary', true);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

    //helper
    public function getUrlAttribute(){
        return asset('storage/' . $this->image_path);
    }
}
