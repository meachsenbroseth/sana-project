<?php

namespace App\Services\Analytics;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reports\TopSellingProductReport;
use Carbon\CarbonInterface;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function __construct(
        protected AnalyticsTableResolver $tables,
    ) {}

    public function isOperational(): bool
    {
        return $this->tables->isOperational();
    }

    public function productsAvailable(): bool
    {
        return $this->tables->hasProducts();
    }

    public function remember(string $key, AnalyticsFilters $filters, callable $callback): mixed
    {
        if (! $this->isOperational()) {
            return $this->emptyResultForKey($key);
        }

        return Cache::remember(
            "analytics.{$key}.{$filters->cacheKey()}",
            now()->addMinutes(5),
            $callback
        );
    }

    public function orderQuery(AnalyticsFilters $filters): Builder
    {
        if (! $this->tables->hasOrders()) {
            return Order::query()->whereRaw('0 = 1');
        }

        return Order::query()
            ->when($filters->startDate, fn (Builder $query) => $query->where('created_at', '>=', $filters->startDate))
            ->when($filters->endDate, fn (Builder $query) => $query->where('created_at', '<=', $filters->endDate))
            ->when($filters->customerId, fn (Builder $query) => $query->where('customer_id', $filters->customerId))
            ->when($filters->orderStatus, fn (Builder $query) => $query->where('status', $filters->orderStatus))
            ->when($filters->paymentMethod, fn (Builder $query) => $query->where('payment_method', $filters->paymentMethod))
            ->when($filters->productId, fn (Builder $query) => $query->whereHas(
                'items',
                fn (Builder $itemQuery) => $itemQuery->where('product_id', $filters->productId),
            ))
            ->when($filters->categoryId && $this->tables->hasProducts(), fn (Builder $query) => $query->whereHas(
                'items.product',
                fn (Builder $productQuery) => $productQuery->where('category_id', $filters->categoryId),
            ));
    }

    public function paidOrderQuery(AnalyticsFilters $filters): Builder
    {
        return $this->orderQuery($filters)->where('payment_status', 'paid');
    }

    /**
     * @return array<string, float|int>
     */
    public function kpiMetrics(AnalyticsFilters $filters): array
    {
        return $this->remember('kpi_metrics', $filters, function () use ($filters): array {
            $orderQuery = $this->orderQuery($filters);
            $paidOrderQuery = $this->paidOrderQuery($filters);

            $totalRevenue = (float) (clone $paidOrderQuery)->sum('total');
            $totalOrders = (int) (clone $orderQuery)->count();
            $totalCustomers = $this->totalCustomers($filters);
            $totalProducts = $this->totalProducts($filters);
            $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0.0;

            $todayOrderQuery = $this->applyNonDateFilters(Order::query(), $filters)
                ->whereDate('created_at', today());
            $todayPaidQuery = (clone $todayOrderQuery)->where('payment_status', 'paid');

            return [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'total_customers' => $totalCustomers,
                'total_products' => $totalProducts,
                'average_order_value' => $averageOrderValue,
                'orders_today' => (int) $todayOrderQuery->count(),
                'revenue_today' => (float) $todayPaidQuery->sum('total'),
                'pending_orders' => (int) (clone $orderQuery)->where('status', 'pending')->count(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function insightMetrics(AnalyticsFilters $filters): array
    {
        return $this->remember('insight_metrics', $filters, function () use ($filters): array {
            $topProduct = $this->productsAvailable()
                ? $this->topSellingProductsQuery($filters)->first()
                : null;

            $mostActiveCustomer = $this->tables->hasCustomers()
                ? $this->customerReportQuery($filters)->orderByDesc('total_orders')->first()
                : null;

            $highestRevenueDay = (clone $this->paidOrderQuery($filters))
                ->selectRaw('DATE(created_at) as revenue_date, SUM(total) as revenue')
                ->groupBy('revenue_date')
                ->orderByDesc('revenue')
                ->first();

            $highestRevenueMonthQuery = clone $this->paidOrderQuery($filters);

            $highestRevenueMonth = DB::getDriverName() === 'sqlite'
                ? $highestRevenueMonthQuery
                    ->selectRaw("strftime('%Y-%m', created_at) as revenue_month, SUM(total) as revenue")
                    ->groupBy('revenue_month')
                    ->orderByDesc('revenue')
                    ->first()
                : $highestRevenueMonthQuery
                    ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as revenue_month, SUM(total) as revenue')
                    ->groupBy('revenue_month')
                    ->orderByDesc('revenue')
                    ->first();

            $popularCategory = $this->resolveMostPopularCategory($filters);

            $averageClv = 0.0;

            if ($this->tables->hasCustomers()) {
                $customerLifetimeValues = Customer::query()
                    ->withSum(['orders as paid_total' => fn (Builder $query) => $query->where('payment_status', 'paid')], 'total')
                    ->having('paid_total', '>', 0)
                    ->pluck('paid_total');

                $averageClv = $customerLifetimeValues->isNotEmpty()
                    ? (float) $customerLifetimeValues->avg()
                    : 0.0;
            }

            return [
                'best_selling_product' => $topProduct?->product_name,
                'most_active_customer' => $mostActiveCustomer?->name,
                'highest_revenue_day' => $highestRevenueDay?->revenue_date,
                'highest_revenue_day_amount' => (float) ($highestRevenueDay?->revenue ?? 0),
                'highest_revenue_month' => $highestRevenueMonth?->revenue_month,
                'highest_revenue_month_amount' => (float) ($highestRevenueMonth?->revenue ?? 0),
                'most_popular_category' => $popularCategory,
                'average_customer_lifetime_value' => $averageClv,
            ];
        });
    }

    public function revenueTrend(AnalyticsFilters $filters, string $period): Collection
    {
        return $this->remember("revenue_trend.{$period}", $filters, function () use ($filters, $period) {
            $query = $this->paidOrderQuery($filters);
            [$start, $end, $per] = $this->resolveTrendPeriod($filters, $period);

            return Trend::query($query)
                ->between($start, $end)
                ->{$per}()
                ->sum('total');
        });
    }

    public function ordersTrend(AnalyticsFilters $filters, string $grouping): Collection
    {
        return $this->remember("orders_trend.{$grouping}", $filters, function () use ($filters, $grouping) {
            $query = $this->orderQuery($filters);
            [$start, $end, $per] = match ($grouping) {
                'month' => $this->resolveTrendPeriod($filters, 'monthly'),
                'status' => [null, null, null],
                default => $this->resolveTrendPeriod($filters, 'daily'),
            };

            if ($grouping === 'status') {
                return $this->orderQuery($filters)
                    ->select('status', DB::raw('COUNT(*) as aggregate'))
                    ->groupBy('status')
                    ->orderByDesc('aggregate')
                    ->get()
                    ->map(fn ($row) => new TrendValue(
                        date: (string) $row->status,
                        aggregate: (int) $row->aggregate,
                    ));
            }

            return Trend::query($query)
                ->between($start, $end)
                ->{$per}()
                ->count();
        });
    }

    public function customerGrowthTrend(AnalyticsFilters $filters, string $grouping): Collection
    {
        return $this->remember("customer_growth.{$grouping}", $filters, function () use ($filters, $grouping) {
            if (! $this->tables->hasCustomers()) {
                return collect();
            }

            $query = Customer::query()
                ->when($filters->customerId, fn (Builder $customerQuery) => $customerQuery->where('id', $filters->customerId))
                ->when($filters->hasOrderScopedFilters(), fn (Builder $customerQuery) => $customerQuery->whereHas(
                    'orders',
                    fn (Builder $orderQuery) => $this->applyNonDateFilters($orderQuery, $filters),
                ));

            [$start, $end, $per] = match ($grouping) {
                'month' => $this->resolveTrendPeriod($filters, 'monthly'),
                default => $this->resolveTrendPeriod($filters, 'daily'),
            };

            return Trend::query($query)
                ->between($start, $end)
                ->{$per}()
                ->count();
        });
    }

    /**
     * @return Collection<int, object>
     */
    public function productPerformance(AnalyticsFilters $filters, string $metric): Collection
    {
        return $this->remember("product_performance.{$metric}", $filters, function () use ($filters, $metric) {
            if (! $this->productsAvailable()) {
                return collect();
            }

            return match ($metric) {
                'most_viewed' => Product::query()
                    ->when($filters->categoryId, fn (Builder $query) => $query->where('category_id', $filters->categoryId))
                    ->when($filters->productId, fn (Builder $query) => $query->where('id', $filters->productId))
                    ->orderByDesc('view_count')
                    ->limit(10)
                    ->get(['name', 'view_count']),
                'highest_revenue' => $this->topSellingProductsQuery($filters)
                    ->orderByDesc('revenue')
                    ->limit(10)
                    ->get(),
                'low_stock' => Product::query()
                    ->lowStock()
                    ->when($filters->categoryId, fn (Builder $query) => $query->where('category_id', $filters->categoryId))
                    ->when($filters->productId, fn (Builder $query) => $query->where('id', $filters->productId))
                    ->orderBy('stock_quantity')
                    ->limit(10)
                    ->get(['name', 'stock_quantity']),
                default => $this->topSellingProductsQuery($filters)
                    ->limit(10)
                    ->get(),
            };
        });
    }

    public function topSellingProductsQuery(AnalyticsFilters $filters): QueryBuilder
    {
        if (! $this->productsAvailable() || ! $this->tables->hasOrderItems()) {
            return $this->emptyTopSellingProductsSubquery();
        }

        $productTable = $this->tables->product();
        $orderTable = $this->tables->order();
        $orderItemTable = $this->tables->orderItem();

        $stockColumn = $this->tables->qualifiedProductColumn('stock_quantity');
        $categoryColumn = $this->tables->qualifiedProductColumn('category_id');

        return DB::table($orderItemTable)
            ->select([
                DB::raw("MIN({$orderItemTable}.product_id) as id"),
                DB::raw("MIN({$orderItemTable}.product_id) as product_id"),
                "{$orderItemTable}.product_name",
                "{$orderItemTable}.product_sku",
                DB::raw("SUM({$orderItemTable}.quantity) as quantity_sold"),
                DB::raw("SUM({$orderItemTable}.total_amount) as revenue"),
                DB::raw("MAX({$stockColumn}) as stock_remaining"),
            ])
            ->join($orderTable, "{$orderTable}.id", '=', "{$orderItemTable}.order_id")
            ->leftJoin($productTable, function ($join) use ($productTable, $orderItemTable): void {
                $join->on("{$productTable}.id", '=', "{$orderItemTable}.product_id");

                if ($this->tables->productUsesSoftDeletes()) {
                    $join->whereNull("{$productTable}.deleted_at");
                }
            })
            ->where("{$orderTable}.payment_status", 'paid')
            ->when($filters->startDate, fn ($query) => $query->where("{$orderTable}.created_at", '>=', $filters->startDate))
            ->when($filters->endDate, fn ($query) => $query->where("{$orderTable}.created_at", '<=', $filters->endDate))
            ->when($filters->customerId, fn ($query) => $query->where("{$orderTable}.customer_id", $filters->customerId))
            ->when($filters->orderStatus, fn ($query) => $query->where("{$orderTable}.status", $filters->orderStatus))
            ->when($filters->paymentMethod, fn ($query) => $query->where("{$orderTable}.payment_method", $filters->paymentMethod))
            ->when($filters->productId, fn ($query) => $query->where("{$orderItemTable}.product_id", $filters->productId))
            ->when($filters->categoryId, fn ($query) => $query->where($categoryColumn, $filters->categoryId))
            ->groupBy("{$orderItemTable}.product_id", "{$orderItemTable}.product_name", "{$orderItemTable}.product_sku");
    }

    public function topSellingProductsEloquentQuery(AnalyticsFilters $filters): Builder
    {
        return TopSellingProductReport::query()
            ->fromSub($this->topSellingProductsQuery($filters), 'top_selling_products')
            ->select('top_selling_products.*');
    }

    public function customerReportQuery(AnalyticsFilters $filters): Builder
    {
        if (! $this->tables->hasCustomers()) {
            return Customer::query()->whereRaw('0 = 1');
        }

        $orderTable = $this->tables->order();
        $orderItemTable = $this->tables->orderItem();
        $productTable = $this->tables->product();
        $categoryColumn = $this->tables->qualifiedProductColumn('category_id');

        return Customer::query()
            ->select("{$this->tables->customer()}.*")
            ->selectSub(function ($query) use ($filters, $orderTable, $orderItemTable, $productTable, $categoryColumn): void {
                $query->from($orderTable)
                    ->selectRaw('COUNT(*)')
                    ->whereColumn("{$orderTable}.customer_id", "{$this->tables->customer()}.id")
                    ->when($filters->startDate, fn ($orderQuery) => $orderQuery->where("{$orderTable}.created_at", '>=', $filters->startDate))
                    ->when($filters->endDate, fn ($orderQuery) => $orderQuery->where("{$orderTable}.created_at", '<=', $filters->endDate))
                    ->when($filters->orderStatus, fn ($orderQuery) => $orderQuery->where("{$orderTable}.status", $filters->orderStatus))
                    ->when($filters->paymentMethod, fn ($orderQuery) => $orderQuery->where("{$orderTable}.payment_method", $filters->paymentMethod))
                    ->when($filters->productId, fn ($orderQuery) => $orderQuery->whereExists(function ($exists) use ($filters, $orderTable, $orderItemTable): void {
                        $exists->select(DB::raw(1))
                            ->from($orderItemTable)
                            ->whereColumn("{$orderItemTable}.order_id", "{$orderTable}.id")
                            ->where("{$orderItemTable}.product_id", $filters->productId);
                    }))
                    ->when($filters->categoryId && $this->productsAvailable(), fn ($orderQuery) => $orderQuery->whereExists(function ($exists) use ($filters, $orderTable, $orderItemTable, $productTable, $categoryColumn): void {
                        $exists->select(DB::raw(1))
                            ->from($orderItemTable)
                            ->join($productTable, "{$productTable}.id", '=', "{$orderItemTable}.product_id")
                            ->whereColumn("{$orderItemTable}.order_id", "{$orderTable}.id")
                            ->where($categoryColumn, $filters->categoryId);
                    }));
            }, 'total_orders')
            ->selectSub(function ($query) use ($filters, $orderTable, $orderItemTable, $productTable, $categoryColumn): void {
                $query->from($orderTable)
                    ->selectRaw('COALESCE(SUM(total), 0)')
                    ->whereColumn("{$orderTable}.customer_id", "{$this->tables->customer()}.id")
                    ->where("{$orderTable}.payment_status", 'paid')
                    ->when($filters->startDate, fn ($orderQuery) => $orderQuery->where("{$orderTable}.created_at", '>=', $filters->startDate))
                    ->when($filters->endDate, fn ($orderQuery) => $orderQuery->where("{$orderTable}.created_at", '<=', $filters->endDate))
                    ->when($filters->orderStatus, fn ($orderQuery) => $orderQuery->where("{$orderTable}.status", $filters->orderStatus))
                    ->when($filters->paymentMethod, fn ($orderQuery) => $orderQuery->where("{$orderTable}.payment_method", $filters->paymentMethod))
                    ->when($filters->productId, fn ($orderQuery) => $orderQuery->whereExists(function ($exists) use ($filters, $orderTable, $orderItemTable): void {
                        $exists->select(DB::raw(1))
                            ->from($orderItemTable)
                            ->whereColumn("{$orderItemTable}.order_id", "{$orderTable}.id")
                            ->where("{$orderItemTable}.product_id", $filters->productId);
                    }))
                    ->when($filters->categoryId && $this->productsAvailable(), fn ($orderQuery) => $orderQuery->whereExists(function ($exists) use ($filters, $orderTable, $orderItemTable, $productTable, $categoryColumn): void {
                        $exists->select(DB::raw(1))
                            ->from($orderItemTable)
                            ->join($productTable, "{$productTable}.id", '=', "{$orderItemTable}.product_id")
                            ->whereColumn("{$orderItemTable}.order_id", "{$orderTable}.id")
                            ->where($categoryColumn, $filters->categoryId);
                    }));
            }, 'total_spent')
            ->selectSub(function ($query) use ($filters, $orderTable, $orderItemTable, $productTable, $categoryColumn): void {
                $query->from($orderTable)
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn("{$orderTable}.customer_id", "{$this->tables->customer()}.id")
                    ->when($filters->startDate, fn ($orderQuery) => $orderQuery->where("{$orderTable}.created_at", '>=', $filters->startDate))
                    ->when($filters->endDate, fn ($orderQuery) => $orderQuery->where("{$orderTable}.created_at", '<=', $filters->endDate))
                    ->when($filters->orderStatus, fn ($orderQuery) => $orderQuery->where("{$orderTable}.status", $filters->orderStatus))
                    ->when($filters->paymentMethod, fn ($orderQuery) => $orderQuery->where("{$orderTable}.payment_method", $filters->paymentMethod))
                    ->when($filters->productId, fn ($orderQuery) => $orderQuery->whereExists(function ($exists) use ($filters, $orderTable, $orderItemTable): void {
                        $exists->select(DB::raw(1))
                            ->from($orderItemTable)
                            ->whereColumn("{$orderItemTable}.order_id", "{$orderTable}.id")
                            ->where("{$orderItemTable}.product_id", $filters->productId);
                    }))
                    ->when($filters->categoryId && $this->productsAvailable(), fn ($orderQuery) => $orderQuery->whereExists(function ($exists) use ($filters, $orderTable, $orderItemTable, $productTable, $categoryColumn): void {
                        $exists->select(DB::raw(1))
                            ->from($orderItemTable)
                            ->join($productTable, "{$productTable}.id", '=', "{$orderItemTable}.product_id")
                            ->whereColumn("{$orderItemTable}.order_id", "{$orderTable}.id")
                            ->where($categoryColumn, $filters->categoryId);
                    }));
            }, 'last_order_date')
            ->when($filters->customerId, fn (Builder $query) => $query->where("{$this->tables->customer()}.id", $filters->customerId))
            ->when($filters->hasOrderScopedFilters() || $filters->startDate || $filters->endDate, fn (Builder $query) => $query->whereHas(
                'orders',
                fn (Builder $orderQuery) => $this->orderQuery($filters),
            ));
    }

    public function orderReportQuery(AnalyticsFilters $filters): Builder
    {
        return $this->orderQuery($filters)
            ->with(['customer:id,name,email'])
            ->latest();
    }

    public function formatCurrency(float $amount): string
    {
        return '$'.number_format($amount, 2);
    }

    protected function resolveMostPopularCategory(AnalyticsFilters $filters): ?string
    {
        if (! $this->productsAvailable() || ! $this->tables->hasCategories()) {
            return null;
        }

        $productTable = $this->tables->product();
        $categoryTable = $this->tables->category();
        $orderTable = $this->tables->order();
        $orderItemTable = $this->tables->orderItem();
        $categoryColumn = $this->tables->qualifiedProductColumn('category_id');

        $result = DB::table($orderItemTable)
            ->select("{$categoryTable}.name as category_name", DB::raw("SUM({$orderItemTable}.quantity) as total_quantity"))
            ->join($orderTable, "{$orderTable}.id", '=', "{$orderItemTable}.order_id")
            ->join($productTable, "{$productTable}.id", '=', "{$orderItemTable}.product_id")
            ->join($categoryTable, "{$categoryTable}.id", '=', $categoryColumn)
            ->where("{$orderTable}.payment_status", 'paid')
            ->when($this->tables->productUsesSoftDeletes(), fn ($query) => $query->whereNull("{$productTable}.deleted_at"))
            ->when($filters->startDate, fn ($query) => $query->where("{$orderTable}.created_at", '>=', $filters->startDate))
            ->when($filters->endDate, fn ($query) => $query->where("{$orderTable}.created_at", '<=', $filters->endDate))
            ->when($filters->customerId, fn ($query) => $query->where("{$orderTable}.customer_id", $filters->customerId))
            ->when($filters->orderStatus, fn ($query) => $query->where("{$orderTable}.status", $filters->orderStatus))
            ->when($filters->paymentMethod, fn ($query) => $query->where("{$orderTable}.payment_method", $filters->paymentMethod))
            ->when($filters->productId, fn ($query) => $query->where("{$orderItemTable}.product_id", $filters->productId))
            ->when($filters->categoryId, fn ($query) => $query->where($categoryColumn, $filters->categoryId))
            ->groupBy("{$categoryTable}.id", "{$categoryTable}.name")
            ->orderByDesc('total_quantity')
            ->first();

        return $result?->category_name;
    }

    protected function totalCustomers(AnalyticsFilters $filters): int
    {
        if (! $this->tables->hasCustomers()) {
            return 0;
        }

        $query = Customer::query()
            ->when($filters->startDate, fn (Builder $customerQuery) => $customerQuery->where('created_at', '>=', $filters->startDate))
            ->when($filters->endDate, fn (Builder $customerQuery) => $customerQuery->where('created_at', '<=', $filters->endDate))
            ->when($filters->customerId, fn (Builder $customerQuery) => $customerQuery->where('id', $filters->customerId));

        if ($filters->hasOrderScopedFilters()) {
            $query->whereHas('orders', fn (Builder $orderQuery) => $this->applyNonDateFilters($orderQuery, $filters));
        }

        return (int) $query->count();
    }

    protected function totalProducts(AnalyticsFilters $filters): int
    {
        if (! $this->productsAvailable()) {
            return 0;
        }

        return (int) Product::query()
            ->when($filters->categoryId, fn (Builder $query) => $query->where('category_id', $filters->categoryId))
            ->when($filters->productId, fn (Builder $query) => $query->where('id', $filters->productId))
            ->count();
    }

    protected function applyNonDateFilters(Builder $query, AnalyticsFilters $filters): Builder
    {
        return $query
            ->when($filters->customerId, fn (Builder $orderQuery) => $orderQuery->where('customer_id', $filters->customerId))
            ->when($filters->orderStatus, fn (Builder $orderQuery) => $orderQuery->where('status', $filters->orderStatus))
            ->when($filters->paymentMethod, fn (Builder $orderQuery) => $orderQuery->where('payment_method', $filters->paymentMethod))
            ->when($filters->productId, fn (Builder $orderQuery) => $orderQuery->whereHas(
                'items',
                fn (Builder $itemQuery) => $itemQuery->where('product_id', $filters->productId),
            ))
            ->when($filters->categoryId && $this->productsAvailable(), fn (Builder $orderQuery) => $orderQuery->whereHas(
                'items.product',
                fn (Builder $productQuery) => $productQuery->where('category_id', $filters->categoryId),
            ));
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: string}
     */
    protected function resolveTrendPeriod(AnalyticsFilters $filters, string $period): array
    {
        $start = $filters->startDate ?? now()->subMonth();
        $end = $filters->endDate ?? now();

        return match ($period) {
            'weekly' => [$start, $end, 'perWeek'],
            'monthly' => [$start, $end, 'perMonth'],
            'yearly' => [$start, $end, 'perYear'],
            default => [$start, $end, 'perDay'],
        };
    }

    protected function emptyTopSellingProductsSubquery(): QueryBuilder
    {
        return DB::table($this->tables->orderItem())
            ->selectRaw('NULL as id, NULL as product_id, NULL as product_name, NULL as product_sku, 0 as quantity_sold, 0 as revenue, 0 as stock_remaining')
            ->whereRaw('0 = 1');
    }

    protected function emptyResultForKey(string $key): mixed
    {
        return match (true) {
            str_starts_with($key, 'revenue_trend'),
            str_starts_with($key, 'orders_trend'),
            str_starts_with($key, 'customer_growth'),
            str_starts_with($key, 'product_performance') => collect(),
            $key === 'kpi_metrics' => [
                'total_revenue' => 0.0,
                'total_orders' => 0,
                'total_customers' => 0,
                'total_products' => 0,
                'average_order_value' => 0.0,
                'orders_today' => 0,
                'revenue_today' => 0.0,
                'pending_orders' => 0,
            ],
            $key === 'insight_metrics' => [
                'best_selling_product' => null,
                'most_active_customer' => null,
                'highest_revenue_day' => null,
                'highest_revenue_day_amount' => 0.0,
                'highest_revenue_month' => null,
                'highest_revenue_month_amount' => 0.0,
                'most_popular_category' => null,
                'average_customer_lifetime_value' => 0.0,
            ],
            default => null,
        };
    }
}
