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
            ->when($filters->startDate, fn(Builder $q) => $q->where('created_at', '>=', $filters->startDate))
            ->when($filters->endDate,   fn(Builder $q) => $q->where('created_at', '<=', $filters->endDate))
            ->when($filters->customerId, fn(Builder $q) => $q->where('customer_id', $filters->customerId))
            ->when($filters->orderStatus, fn(Builder $q) => $q->where('status', $filters->orderStatus))
            ->when($filters->paymentMethod, fn(Builder $q) => $q->where('payment_method', $filters->paymentMethod))
            ->when($filters->productId, fn(Builder $q) => $q->whereHas(
                'items',
                fn(Builder $iq) => $iq->where('product_id', $filters->productId),
            ))
            ->when($filters->categoryId && $this->tables->hasProducts(), fn(Builder $q) => $q->whereHas(
                'items.product',
                fn(Builder $pq) => $pq->where('category_id', $filters->categoryId),
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
            $orderQuery     = $this->orderQuery($filters);
            $paidOrderQuery = $this->paidOrderQuery($filters);

            $totalRevenue   = (float) (clone $paidOrderQuery)->sum('total');
            $totalOrders    = (int)   (clone $orderQuery)->count();
            $totalCustomers = $this->totalCustomers($filters);
            $totalProducts  = $this->totalProducts($filters);
            $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0.0;

            $todayOrderQuery = $this->applyNonDateFilters(Order::query(), $filters)
                ->whereDate('created_at', today());
            $todayPaidQuery = (clone $todayOrderQuery)->where('payment_status', 'paid');

            return [
                'total_revenue'       => $totalRevenue,
                'total_orders'        => $totalOrders,
                'total_customers'     => $totalCustomers,
                'total_products'      => $totalProducts,
                'average_order_value' => $averageOrderValue,
                'orders_today'        => (int)   $todayOrderQuery->count(),
                'revenue_today'       => (float) $todayPaidQuery->sum('total'),
                'pending_orders'      => (int)   (clone $orderQuery)->where('status', 'pending')->count(),
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

            $highestRevenueMonth = $this->resolveHighestRevenueMonth($filters);
            $popularCategory     = $this->resolveMostPopularCategory($filters);
            $averageClv          = $this->resolveAverageClv();

            return [
                'best_selling_product'           => $topProduct?->product_name,
                'most_active_customer'            => $mostActiveCustomer?->name,
                'highest_revenue_day'             => $highestRevenueDay?->revenue_date,
                'highest_revenue_day_amount'      => (float) ($highestRevenueDay?->revenue ?? 0),
                'highest_revenue_month'           => $highestRevenueMonth?->revenue_month,
                'highest_revenue_month_amount'    => (float) ($highestRevenueMonth?->revenue ?? 0),
                'most_popular_category'           => $popularCategory,
                'average_customer_lifetime_value' => $averageClv,
            ];
        });
    }

    public function revenueTrend(AnalyticsFilters $filters, string $period): Collection
    {
        return $this->remember("revenue_trend.{$period}", $filters, function () use ($filters, $period) {
            [$start, $end, $per] = $this->resolveTrendPeriod($filters, $period);

            return Trend::query($this->paidOrderQuery($filters))
                ->between($start, $end)
                ->{$per}()
                ->sum('total');
        });
    }

    public function ordersTrend(AnalyticsFilters $filters, string $grouping): Collection
    {
        return $this->remember("orders_trend.{$grouping}", $filters, function () use ($filters, $grouping) {
            if ($grouping === 'status') {
                return $this->orderQuery($filters)
                    ->select('status', DB::raw('COUNT(*) as aggregate'))
                    ->groupBy('status')
                    ->orderByDesc('aggregate')
                    ->get()
                    ->map(fn($row) => new TrendValue(
                        date:      (string) $row->status,
                        aggregate: (int)    $row->aggregate,
                    ));
            }

            [$start, $end, $per] = match ($grouping) {
                'month' => $this->resolveTrendPeriod($filters, 'monthly'),
                default => $this->resolveTrendPeriod($filters, 'daily'),
            };

            return Trend::query($this->orderQuery($filters))
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
                ->when($filters->customerId, fn(Builder $q) => $q->where('id', $filters->customerId))
                ->when($filters->hasOrderScopedFilters(), fn(Builder $q) => $q->whereHas(
                    'orders',
                    fn(Builder $oq) => $this->applyNonDateFilters($oq, $filters),
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
                    ->when($filters->categoryId, fn(Builder $q) => $q->where('category_id', $filters->categoryId))
                    ->when($filters->productId,  fn(Builder $q) => $q->where('id', $filters->productId))
                    ->orderByDesc('view_count')
                    ->limit(10)
                    ->get(['name', 'view_count']),

                'highest_revenue' => $this->topSellingProductsQuery($filters)
                    ->orderByDesc('revenue')
                    ->limit(10)
                    ->get(),

                'low_stock' => Product::query()
                    ->lowStock()
                    ->when($filters->categoryId, fn(Builder $q) => $q->where('category_id', $filters->categoryId))
                    ->when($filters->productId,  fn(Builder $q) => $q->where('id', $filters->productId))
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

        $productTable   = $this->tables->product();
        $orderTable     = $this->tables->order();
        $orderItemTable = $this->tables->orderItem();
        $stockColumn    = $this->tables->qualifiedProductColumn('stock_quantity');
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
            ->when($filters->startDate,     fn($q) => $q->where("{$orderTable}.created_at", '>=', $filters->startDate))
            ->when($filters->endDate,       fn($q) => $q->where("{$orderTable}.created_at", '<=', $filters->endDate))
            ->when($filters->customerId,    fn($q) => $q->where("{$orderTable}.customer_id", $filters->customerId))
            ->when($filters->orderStatus,   fn($q) => $q->where("{$orderTable}.status", $filters->orderStatus))
            ->when($filters->paymentMethod, fn($q) => $q->where("{$orderTable}.payment_method", $filters->paymentMethod))
            ->when($filters->productId,     fn($q) => $q->where("{$orderItemTable}.product_id", $filters->productId))
            ->when($filters->categoryId,    fn($q) => $q->where($categoryColumn, $filters->categoryId))
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

        $customerTable  = $this->tables->customer();
        $orderTable     = $this->tables->order();
        $orderItemTable = $this->tables->orderItem();
        $productTable   = $this->tables->product();
        $categoryColumn = $this->tables->qualifiedProductColumn('category_id');

        // Reusable closure to apply order-scoped filters to a sub-query builder
        $applyOrderFilters = function ($query) use ($filters, $orderTable, $orderItemTable, $productTable, $categoryColumn): void {
            $query
                ->when($filters->startDate,     fn($q) => $q->where("{$orderTable}.created_at", '>=', $filters->startDate))
                ->when($filters->endDate,       fn($q) => $q->where("{$orderTable}.created_at", '<=', $filters->endDate))
                ->when($filters->orderStatus,   fn($q) => $q->where("{$orderTable}.status", $filters->orderStatus))
                ->when($filters->paymentMethod, fn($q) => $q->where("{$orderTable}.payment_method", $filters->paymentMethod))
                ->when($filters->productId, fn($q) => $q->whereExists(function ($ex) use ($filters, $orderTable, $orderItemTable): void {
                    $ex->select(DB::raw(1))
                        ->from($orderItemTable)
                        ->whereColumn("{$orderItemTable}.order_id", "{$orderTable}.id")
                        ->where("{$orderItemTable}.product_id", $filters->productId);
                }))
                ->when($filters->categoryId && $this->productsAvailable(), fn($q) => $q->whereExists(function ($ex) use ($filters, $orderTable, $orderItemTable, $productTable, $categoryColumn): void {
                    $ex->select(DB::raw(1))
                        ->from($orderItemTable)
                        ->join($productTable, "{$productTable}.id", '=', "{$orderItemTable}.product_id")
                        ->whereColumn("{$orderItemTable}.order_id", "{$orderTable}.id")
                        ->where($categoryColumn, $filters->categoryId);
                }));
        };

        return Customer::query()
            ->select("{$customerTable}.*")
            ->selectSub(function ($q) use ($orderTable, $applyOrderFilters, $customerTable): void {
                $q->from($orderTable)
                    ->selectRaw('COUNT(*)')
                    ->whereColumn("{$orderTable}.customer_id", "{$customerTable}.id");
                $applyOrderFilters($q);
            }, 'total_orders')
            ->selectSub(function ($q) use ($orderTable, $applyOrderFilters, $customerTable): void {
                $q->from($orderTable)
                    ->selectRaw('COALESCE(SUM(total), 0)')
                    ->whereColumn("{$orderTable}.customer_id", "{$customerTable}.id")
                    ->where("{$orderTable}.payment_status", 'paid');
                $applyOrderFilters($q);
            }, 'total_spent')
            ->selectSub(function ($q) use ($orderTable, $applyOrderFilters, $customerTable): void {
                $q->from($orderTable)
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn("{$orderTable}.customer_id", "{$customerTable}.id");
                $applyOrderFilters($q);
            }, 'last_order_date')
            ->when($filters->customerId, fn(Builder $q) => $q->where("{$customerTable}.id", $filters->customerId))
            ->when(
                $filters->hasOrderScopedFilters() || $filters->startDate || $filters->endDate,
                fn(Builder $q) => $q->whereHas('orders', fn(Builder $oq) => $this->orderQuery($filters))
            );
    }

    public function orderReportQuery(AnalyticsFilters $filters): Builder
    {
        return $this->orderQuery($filters)
            ->with(['customer:id,name,email'])
            ->latest();
    }

    public function formatCurrency(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Protected helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fix for PostgreSQL: aliases defined in SELECT are not visible in WHERE.
     * Use a wrapping subquery so the filter runs on the derived column.
     */
    protected function resolveAverageClv(): float
    {
        if (! $this->tables->hasCustomers()) {
            return 0.0;
        }

        $orderTable    = $this->tables->order();
        $customerTable = $this->tables->customer();

        // Build the paid-total subquery as a raw expression so it works on
        // every supported driver without referencing the alias in WHERE.
        $sub = DB::table($customerTable)
            ->selectRaw(
                "(SELECT COALESCE(SUM(o.total), 0) FROM {$orderTable} o " .
                "WHERE o.customer_id = {$customerTable}.id " .
                "AND o.payment_status = 'paid') AS paid_total"
            );

        // Wrap in an outer query and filter there — safe on pgsql / mysql / sqlite.
        $values = DB::table(DB::raw("({$sub->toSql()}) as clv_sub"))
            ->mergeBindings($sub)
            ->where('paid_total', '>', 0)
            ->pluck('paid_total');

        return $values->isNotEmpty() ? (float) $values->avg() : 0.0;
    }

    protected function resolveHighestRevenueMonth(AnalyticsFilters $filters): ?object
    {
        $query = clone $this->paidOrderQuery($filters);

        return match (DB::getDriverName()) {
            'sqlite' => $query
                ->selectRaw("strftime('%Y-%m', created_at) as revenue_month, SUM(total) as revenue")
                ->groupBy('revenue_month')
                ->orderByDesc('revenue')
                ->first(),

            'pgsql' => $query
                ->selectRaw("to_char(created_at, 'YYYY-MM') as revenue_month, SUM(total) as revenue")
                ->groupBy(DB::raw("to_char(created_at, 'YYYY-MM')"))
                ->orderByDesc('revenue')
                ->first(),

            default => $query // MySQL / MariaDB
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as revenue_month, SUM(total) as revenue')
                ->groupBy('revenue_month')
                ->orderByDesc('revenue')
                ->first(),
        };
    }

    protected function resolveMostPopularCategory(AnalyticsFilters $filters): ?string
    {
        if (! $this->productsAvailable() || ! $this->tables->hasCategories()) {
            return null;
        }

        $productTable   = $this->tables->product();
        $categoryTable  = $this->tables->category();
        $orderTable     = $this->tables->order();
        $orderItemTable = $this->tables->orderItem();
        $categoryColumn = $this->tables->qualifiedProductColumn('category_id');

        $result = DB::table($orderItemTable)
            ->select("{$categoryTable}.name as category_name", DB::raw("SUM({$orderItemTable}.quantity) as total_quantity"))
            ->join($orderTable,    "{$orderTable}.id",    '=', "{$orderItemTable}.order_id")
            ->join($productTable,  "{$productTable}.id",  '=', "{$orderItemTable}.product_id")
            ->join($categoryTable, "{$categoryTable}.id", '=', $categoryColumn)
            ->where("{$orderTable}.payment_status", 'paid')
            ->when($this->tables->productUsesSoftDeletes(), fn($q) => $q->whereNull("{$productTable}.deleted_at"))
            ->when($filters->startDate,     fn($q) => $q->where("{$orderTable}.created_at", '>=', $filters->startDate))
            ->when($filters->endDate,       fn($q) => $q->where("{$orderTable}.created_at", '<=', $filters->endDate))
            ->when($filters->customerId,    fn($q) => $q->where("{$orderTable}.customer_id", $filters->customerId))
            ->when($filters->orderStatus,   fn($q) => $q->where("{$orderTable}.status", $filters->orderStatus))
            ->when($filters->paymentMethod, fn($q) => $q->where("{$orderTable}.payment_method", $filters->paymentMethod))
            ->when($filters->productId,     fn($q) => $q->where("{$orderItemTable}.product_id", $filters->productId))
            ->when($filters->categoryId,    fn($q) => $q->where($categoryColumn, $filters->categoryId))
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
            ->when($filters->startDate,  fn(Builder $q) => $q->where('created_at', '>=', $filters->startDate))
            ->when($filters->endDate,    fn(Builder $q) => $q->where('created_at', '<=', $filters->endDate))
            ->when($filters->customerId, fn(Builder $q) => $q->where('id', $filters->customerId));

        if ($filters->hasOrderScopedFilters()) {
            $query->whereHas('orders', fn(Builder $oq) => $this->applyNonDateFilters($oq, $filters));
        }

        return (int) $query->count();
    }

    protected function totalProducts(AnalyticsFilters $filters): int
    {
        if (! $this->productsAvailable()) {
            return 0;
        }

        return (int) Product::query()
            ->when($filters->categoryId, fn(Builder $q) => $q->where('category_id', $filters->categoryId))
            ->when($filters->productId,  fn(Builder $q) => $q->where('id', $filters->productId))
            ->count();
    }

    protected function applyNonDateFilters(Builder $query, AnalyticsFilters $filters): Builder
    {
        return $query
            ->when($filters->customerId,    fn(Builder $q) => $q->where('customer_id', $filters->customerId))
            ->when($filters->orderStatus,   fn(Builder $q) => $q->where('status', $filters->orderStatus))
            ->when($filters->paymentMethod, fn(Builder $q) => $q->where('payment_method', $filters->paymentMethod))
            ->when($filters->productId, fn(Builder $q) => $q->whereHas(
                'items',
                fn(Builder $iq) => $iq->where('product_id', $filters->productId),
            ))
            ->when($filters->categoryId && $this->productsAvailable(), fn(Builder $q) => $q->whereHas(
                'items.product',
                fn(Builder $pq) => $pq->where('category_id', $filters->categoryId),
            ));
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: string}
     */
    protected function resolveTrendPeriod(AnalyticsFilters $filters, string $period): array
    {
        $start = $filters->startDate ?? now()->subMonth();
        $end   = $filters->endDate   ?? now();

        return match ($period) {
            'weekly'  => [$start, $end, 'perWeek'],
            'monthly' => [$start, $end, 'perMonth'],
            'yearly'  => [$start, $end, 'perYear'],
            default   => [$start, $end, 'perDay'],
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
                'total_revenue'       => 0.0,
                'total_orders'        => 0,
                'total_customers'     => 0,
                'total_products'      => 0,
                'average_order_value' => 0.0,
                'orders_today'        => 0,
                'revenue_today'       => 0.0,
                'pending_orders'      => 0,
            ],

            $key === 'insight_metrics' => [
                'best_selling_product'           => null,
                'most_active_customer'           => null,
                'highest_revenue_day'            => null,
                'highest_revenue_day_amount'     => 0.0,
                'highest_revenue_month'          => null,
                'highest_revenue_month_amount'   => 0.0,
                'most_popular_category'          => null,
                'average_customer_lifetime_value'=> 0.0,
            ],

            default => null,
        };
    }
}
