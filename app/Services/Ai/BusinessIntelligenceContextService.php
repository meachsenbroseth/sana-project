<?php

namespace App\Services\Ai;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BusinessIntelligenceContextService
{
    public function __construct(
        protected AnalyticsService $analytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return Cache::remember('ai.business_assistant.context', now()->addMinutes(5), function (): array {
            $thisMonth = AnalyticsFilters::fromPageFilters([
                'date_preset' => AnalyticsFilters::PRESET_THIS_MONTH,
            ]);
            $lastMonth = AnalyticsFilters::fromPageFilters([
                'date_preset' => AnalyticsFilters::PRESET_CUSTOM,
                'start_date' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'end_date' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ]);
            $thisYear = AnalyticsFilters::fromPageFilters([
                'date_preset' => AnalyticsFilters::PRESET_THIS_YEAR,
            ]);
            $today = AnalyticsFilters::fromPageFilters([
                'date_preset' => AnalyticsFilters::PRESET_TODAY,
            ]);

            $currentMonthRevenue = (float) $this->analytics->paidOrderQuery($thisMonth)->sum('total');
            $previousMonthRevenue = (float) $this->analytics->paidOrderQuery($lastMonth)->sum('total');

            return [
                'generated_at' => now()->toDateTimeString(),
                'sales' => [
                    'total_revenue' => (float) Order::query()->where('payment_status', 'paid')->sum('total'),
                    'revenue_today' => (float) $this->analytics->paidOrderQuery($today)->sum('total'),
                    'revenue_this_month' => $currentMonthRevenue,
                    'revenue_this_year' => (float) $this->analytics->paidOrderQuery($thisYear)->sum('total'),
                    'revenue_growth_percent' => $this->growthPercent($currentMonthRevenue, $previousMonthRevenue),
                    'average_order_value' => $this->averageOrderValue(),
                    'next_month_revenue_estimate' => $this->nextMonthRevenueEstimate(),
                ],
                'orders' => $this->orderStatusCounts(),
                'products' => [
                    'best_selling' => $this->topSellingProducts(direction: 'desc'),
                    'worst_performing' => $this->topSellingProducts(direction: 'asc'),
                    'most_viewed' => $this->mostViewedProducts(),
                    'out_of_stock' => $this->outOfStockProducts(),
                    'low_stock' => $this->lowStockProducts(),
                    'category_performance' => $this->categoryPerformance(),
                    'fast_moving' => $this->movingProducts(direction: 'desc'),
                    'slow_moving' => $this->movingProducts(direction: 'asc'),
                ],
                'customers' => [
                    'total_customers' => (int) Customer::query()->count(),
                    'new_customers' => (int) Customer::query()->where('created_at', '>=', now()->startOfMonth())->count(),
                    'returning_customers' => $this->returningCustomerCount(),
                    'top_spending_customers' => $this->topSpendingCustomers(),
                    'average_customer_lifetime_value' => $this->averageCustomerLifetimeValue(),
                ],
                'inventory' => [
                    'inventory_value' => $this->inventoryValue(),
                    'low_stock_alerts' => $this->lowStockProducts(limit: 15),
                    'overstocked_products' => $this->overstockedProducts(),
                    'dead_stock' => $this->deadStockProducts(),
                    'restock_recommendations' => $this->restockRecommendations(),
                ],
                'insights' => $this->insights(),
                'recommended_actions' => $this->recommendedActions(),
            ];
        });
    }

    public function contextForPrompt(): string
    {
        return json_encode($this->snapshot(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    protected function averageOrderValue(): float
    {
        $paidOrders = Order::query()->where('payment_status', 'paid');
        $count = (int) (clone $paidOrders)->count();

        return $count > 0 ? round((float) (clone $paidOrders)->sum('total') / $count, 2) : 0.0;
    }

    protected function nextMonthRevenueEstimate(): float
    {
        $monthlyRevenue = Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonthsNoOverflow(6)->startOfMonth())
            ->selectRaw($this->monthExpression().' as revenue_month, SUM(total) as revenue')
            ->groupBy('revenue_month')
            ->orderBy('revenue_month')
            ->pluck('revenue');

        if ($monthlyRevenue->isEmpty()) {
            return 0.0;
        }

        return round((float) $monthlyRevenue->avg(), 2);
    }

    /**
     * @return array<string, int>
     */
    protected function orderStatusCounts(): array
    {
        $counts = Order::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total_orders' => (int) Order::query()->count(),
            'pending_orders' => (int) ($counts['pending'] ?? 0),
            'processing_orders' => (int) ($counts['processing'] ?? 0),
            'shipped_orders' => (int) ($counts['shipped'] ?? 0),
            'delivered_orders' => (int) ($counts['delivered'] ?? 0),
            'cancelled_orders' => (int) ($counts['cancelled'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function topSellingProducts(string $direction, int $limit = 8): array
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.total_amount) as revenue'),
            ])
            ->groupBy('order_items.product_id', 'order_items.product_name');

        $direction === 'desc'
            ? $query->orderByDesc('quantity_sold')
            : $query->orderBy('quantity_sold');

        return $query->limit($limit)->get()->map(fn (object $row): array => [
            'product_id' => (int) $row->product_id,
            'name' => (string) $row->product_name,
            'quantity_sold' => (int) $row->quantity_sold,
            'revenue' => round((float) $row->revenue, 2),
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mostViewedProducts(int $limit = 8): array
    {
        return Product::query()
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get(['id', 'name', 'view_count'])
            ->map(fn (Product $product): array => [
                'product_id' => $product->id,
                'name' => $product->name,
                'views' => (int) $product->view_count,
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function outOfStockProducts(int $limit = 15): array
    {
        return Product::query()
            ->where(fn (Builder $query) => $query
                ->where('stock_status', 'out_of_stock')
                ->orWhere('stock_quantity', '<=', 0))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'stock_quantity'])
            ->map(fn (Product $product): array => [
                'product_id' => $product->id,
                'name' => $product->name,
                'stock_quantity' => (int) $product->stock_quantity,
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function lowStockProducts(int $limit = 10): array
    {
        return Product::query()
            ->lowStock()
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get(['id', 'name', 'stock_quantity', 'low_stock_threshold'])
            ->map(fn (Product $product): array => [
                'product_id' => $product->id,
                'name' => $product->name,
                'stock_quantity' => (int) $product->stock_quantity,
                'low_stock_threshold' => (int) $product->low_stock_threshold,
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function categoryPerformance(int $limit = 8): array
    {
        return Category::query()
            ->leftJoin('products', 'products.category_id', '=', 'categories.id')
            ->leftJoin('order_items', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('orders', function ($join): void {
                $join->on('orders.id', '=', 'order_items.order_id')
                    ->where('orders.payment_status', '=', 'paid');
            })
            ->select([
                'categories.id',
                'categories.name',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as quantity_sold'),
                DB::raw('COALESCE(SUM(order_items.total_amount), 0) as revenue'),
            ])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'category_id' => (int) $row->id,
                'name' => (string) $row->name,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => round((float) $row->revenue, 2),
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function movingProducts(string $direction, int $limit = 8): array
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
            ])
            ->groupBy('order_items.product_id', 'order_items.product_name');

        $direction === 'desc'
            ? $query->orderByDesc('quantity_sold')
            : $query->orderBy('quantity_sold');

        return $query->limit($limit)->get()->map(fn (object $row): array => [
            'product_id' => (int) $row->product_id,
            'name' => (string) $row->product_name,
            'quantity_sold_30_days' => (int) $row->quantity_sold,
        ])->all();
    }

    protected function returningCustomerCount(): int
    {
        return (int) Customer::query()
            ->whereHas('orders', fn (Builder $query) => $query->where('payment_status', 'paid'), '>=', 2)
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function topSpendingCustomers(int $limit = 8): array
    {
        return Customer::query()
            ->select('customers.id', 'customers.name')
            ->selectSub(function ($query): void {
                $query->from('orders')
                    ->selectRaw('COALESCE(SUM(total), 0)')
                    ->whereColumn('orders.customer_id', 'customers.id')
                    ->where('orders.payment_status', 'paid');
            }, 'total_spent')
            ->selectSub(function ($query): void {
                $query->from('orders')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('orders.customer_id', 'customers.id');
            }, 'total_orders')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get()
            ->map(fn (Customer $customer): array => [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'total_spent' => round((float) $customer->total_spent, 2),
                'total_orders' => (int) $customer->total_orders,
            ])->all();
    }

    protected function averageCustomerLifetimeValue(): float
    {
        $values = Customer::query()
            ->selectSub(function ($query): void {
                $query->from('orders')
                    ->selectRaw('COALESCE(SUM(total), 0)')
                    ->whereColumn('orders.customer_id', 'customers.id')
                    ->where('orders.payment_status', 'paid');
            }, 'lifetime_value')
            ->pluck('lifetime_value');

        return $values->isNotEmpty() ? round((float) $values->avg(), 2) : 0.0;
    }

    protected function inventoryValue(): float
    {
        return round((float) DB::table('products')
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(stock_quantity * COALESCE(cost_price, price)), 0) as inventory_value')
            ->value('inventory_value'), 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function overstockedProducts(int $limit = 10): array
    {
        return Product::query()
            ->where(function (Builder $query): void {
                $query->whereColumn('stock_quantity', '>', DB::raw('low_stock_threshold * 5'))
                    ->where('stock_quantity', '>', 20);
            })
            ->orderByDesc('stock_quantity')
            ->limit($limit)
            ->get(['id', 'name', 'stock_quantity', 'low_stock_threshold'])
            ->map(fn (Product $product): array => [
                'product_id' => $product->id,
                'name' => $product->name,
                'stock_quantity' => (int) $product->stock_quantity,
                'low_stock_threshold' => (int) $product->low_stock_threshold,
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function deadStockProducts(int $limit = 10): array
    {
        $soldRecently = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', now()->subDays(90))
            ->pluck('order_items.product_id')
            ->unique();

        return Product::query()
            ->where('stock_quantity', '>', 0)
            ->whereNotIn('id', $soldRecently)
            ->orderByDesc('stock_quantity')
            ->limit($limit)
            ->get(['id', 'name', 'stock_quantity'])
            ->map(fn (Product $product): array => [
                'product_id' => $product->id,
                'name' => $product->name,
                'stock_quantity' => (int) $product->stock_quantity,
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function restockRecommendations(int $limit = 8): array
    {
        return collect($this->lowStockProducts($limit))
            ->map(function (array $product): array {
                $monthlyDemand = (int) DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.payment_status', 'paid')
                    ->where('orders.created_at', '>=', now()->subDays(30))
                    ->where('order_items.product_id', $product['product_id'])
                    ->sum('order_items.quantity');

                $dailyDemand = max($monthlyDemand / 30, 0.1);
                $daysUntilStockout = (int) floor($product['stock_quantity'] / $dailyDemand);

                return $product + [
                    'estimated_monthly_demand' => $monthlyDemand,
                    'estimated_days_until_stockout' => $daysUntilStockout,
                    'recommended_reorder_quantity' => max($monthlyDemand, (int) $product['low_stock_threshold'] * 2),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function insights(): array
    {
        $snapshot = [
            count($this->lowStockProducts()).' products are currently low in stock.',
            count($this->outOfStockProducts()).' products are out of stock.',
            'Estimated next month revenue is '.$this->formatCurrency($this->nextMonthRevenueEstimate()).'.',
            'Average customer lifetime value is '.$this->formatCurrency($this->averageCustomerLifetimeValue()).'.',
        ];

        return $snapshot;
    }

    /**
     * @return array<int, string>
     */
    protected function recommendedActions(): array
    {
        return [
            'Restock fast-moving low-stock products before promotions.',
            'Promote slow-moving and dead-stock products with bundles or limited discounts.',
            'Review overstocked products before placing new purchase orders.',
            'Create retention campaigns for returning customers and top spenders.',
        ];
    }

    protected function growthPercent(float $current, float $previous): float
    {
        if ($previous <= 0.0) {
            return $current > 0.0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    protected function monthExpression(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : 'DATE_FORMAT(created_at, "%Y-%m")';
    }

    protected function formatCurrency(float $amount): string
    {
        return '$'.number_format($amount, 2);
    }
}
