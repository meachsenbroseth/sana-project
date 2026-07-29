<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSimilarity;
use Illuminate\Support\Collection;

class RecommendationService
{
    /**
     * Get products similar to the given product based on behavioral interaction data.
     *
     * @return Collection<int, Product>
     */
    public function similarByBehavior(Product $product, int $limit = 6): Collection
    {
        $relatedIds = ProductSimilarity::query()
            ->where('product_id', $product->id)
            ->orderByDesc('score')
            ->limit($limit)
            ->pluck('related_id');

        if ($relatedIds->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $relatedIds)
            ->where('is_active', true)
            ->with(['brand', 'category', 'primeImage'])
            ->orderByRaw('array_position(ARRAY['.$relatedIds->implode(',').']::bigint[], id)')
            ->get();
    }
}
