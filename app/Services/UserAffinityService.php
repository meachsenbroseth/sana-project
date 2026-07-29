<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserAffinityService
{
    /**
     * Get the customer's top category affinities based on interactions with exponential recency decay.
     *
     * @return Collection<int, object{category_id: int, category_name: string, affinity_score: float}>
     */
    public function categoryAffinity(Customer $customer, int $limit = 5): Collection
    {
        $halfLife = (int) config('recommendations.decay_half_life_days', 30);

        return DB::query()
            ->select([
                'p.category_id',
                'c.name as category_name',
                DB::raw("SUM(ui.weight * POWER(0.5, EXTRACT(EPOCH FROM (NOW() - ui.created_at)) / (86400 * {$halfLife}))) as affinity_score"),
            ])
            ->from('user_interactions as ui')
            ->join('products as p', 'p.id', '=', 'ui.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->where('ui.customer_id', $customer->id)
            ->groupBy('p.category_id', 'c.name')
            ->orderByDesc('affinity_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recommended products for a customer based on their category affinity,
     * excluding already-purchased products.
     *
     * @return Collection<int, Product>
     */
    public function recommendedForUser(Customer $customer, int $limit = 8): Collection
    {
        $topCategories = $this->categoryAffinity($customer);

        if ($topCategories->isEmpty()) {
            return collect();
        }

        $categoryIds = $topCategories->pluck('category_id')->all();

        $purchasedProductIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.customer_id', $customer->id)
            ->pluck('order_items.product_id')
            ->unique()
            ->all();

        return Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $purchasedProductIds)
            ->with(['brand', 'category', 'primeImage'])
            ->orderByRaw('array_position(ARRAY['.implode(',', $categoryIds).']::bigint[], category_id)')
            ->limit($limit)
            ->get();
    }
}
