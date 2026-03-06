<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;
    

    protected $withCount = [
        'approvedReviews as reviews_count',
    ];

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'compare_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'manage_stock',
        'stock_status',
        'status',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'view_count',
        'embedding',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'view_count' => 'integer',
        'manage_stock' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'embedding' => 'array',
    ];

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope to only featured products
     */
    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * Scope to only in-stock products
     */
    #[Scope]
    protected function inStock(Builder $query): void
    {
        $query->where('stock_status', 'in_stock')
            ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope to products with low stock
     */
    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope to filter by category
     */
    #[Scope]
    protected function inCategory(Builder $query, int $categoryId): void
    {
        $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter by brand
     */
    #[Scope]
    protected function ofBrand(Builder $query, int $brandId): void
    {
        $query->where('brand_id', $brandId);
    }

    /**
     * Scope to filter by price range
     */
    #[Scope]
    protected function inPriceRange(Builder $query, float $min, float $max): void
    {
        $query->whereBetween('price', [$min, $max]);
    }

    // relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primeImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)
            ->where('is_approved', true)
            ->latest();
    }

    /**
     * Get the text representation used for generating embeddings (name, brand, category, description).
     */
    public function toEmbeddingText(): string
    {
        return implode(' ', array_filter([
            $this->name,
            $this->brand?->name,
            $this->category?->name,
            $this->description,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    // helpers methods
    public function getDiscountPercentageAttribute(): int
    {
        if ($this->compare_price && $this->compare_price > $this->price) {
            return round((($this->compare_price - $this->price) / $this->compare_price) * 100);
        }

        return 0;
    }

    public function getAverageRatingAttribute(): float
    {
        return round((float) ($this->approvedReviews()->avg('rating') ?? 0), 1);
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * Get products mathematically similar to this one using vector search.
     */
    public function similarProducts(int $limit = 4)
    {
        // Fallback: If this product hasn't been embedded yet, just return same-category items
        if (empty($this->embedding)) {
            return static::query()
                ->where('id', '!=', $this->id)
                ->where('is_active', true)
                ->where('stock_status', 'in_stock')
                ->where('category_id', $this->category_id)
                ->with(['brand', 'category', 'primeImage'])
                ->limit($limit)
                ->get();
        }

        $cacheKey = sprintf('product:%d:similar:%d', $this->id, $limit);

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($limit) {
            
            // Format the PHP array into a string Postgres understands: '[0.1, -0.02, ...]'
            $embeddingString = '[' . implode(',', $this->embedding) . ']';

            return static::query()
                ->where('id', '!=', $this->id)
                ->where('is_active', true)
                ->where('stock_status', 'in_stock')
                ->whereNotNull('embedding')
                // pgvector <-> operator calculates Euclidean distance
                ->orderByRaw('embedding <-> ?::vector', [$embeddingString])
                ->with(['brand', 'category', 'primeImage'])
                ->limit($limit)
                ->get();
        });
    }


    // Events
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $product): void {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'SKU-'.strtoupper(Str::random(8));
            }
        });

        static::updating(function (self $product): void {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
