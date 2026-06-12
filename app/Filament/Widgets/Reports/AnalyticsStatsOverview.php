<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use App\Models\Customer;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsStatsOverview extends StatsOverviewWidget
{
    use InteractsWithAnalytics;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected const CRITICAL_STOCK_THRESHOLD = 5;

    protected function getColumns(): int
    {
        return 4;
    }

    public function getHeading(): ?string
    {
        return __('analytics.widgets.kpi_heading');
    }

    protected function getStats(): array
    {
        $metrics = $this->analytics()->kpiMetrics($this->filters());
        $format  = fn(float $amount): string => $this->analytics()->formatCurrency($amount);
        $filters = $this->filters();

        // ── Context labels ────────────────────────────────────────────────────

        $pendingLabel = number_format($metrics['pending_orders']) . ' ' . __('analytics.kpis.pending');
        $aovLabel     = __('analytics.kpis.aov_desc', ['value' => $format($metrics['average_order_value'])]);

        // ── Stock counts — three separate, correct queries ────────────────────

        // Out-of-stock: stock_status column = 'out_of_stock' OR stock_quantity = 0
        // (using the actual stock_status enum column in your schema)
        $outOfStockCount = (int) DB::table('products')
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->where('stock_status', 'out_of_stock')
                  ->orWhere('stock_quantity', '<=', 0);
            })
            ->when($filters->categoryId, fn($q) => $q->where('category_id', $filters->categoryId))
            ->when($filters->productId,  fn($q) => $q->where('id', $filters->productId))
            ->count();

        // Critical stock: in-stock but quantity between 1 and CRITICAL threshold
        $criticalCount = (int) DB::table('products')
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', self::CRITICAL_STOCK_THRESHOLD)
            ->where('stock_status', '!=', 'out_of_stock')
            ->when($filters->categoryId, fn($q) => $q->where('category_id', $filters->categoryId))
            ->when($filters->productId,  fn($q) => $q->where('id', $filters->productId))
            ->count();

        // Low stock: in-stock but quantity <= their individual low_stock_threshold
        // (excludes out-of-stock and critical — just the "warning zone")
        $lowStockTotal = (int) DB::table('products')
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_status', '!=', 'out_of_stock')
            ->when($filters->categoryId, fn($q) => $q->where('category_id', $filters->categoryId))
            ->when($filters->productId,  fn($q) => $q->where('id', $filters->productId))
            ->count();

        // Healthy stock: active products that are NOT low/critical/out-of-stock
        $healthyCount = (int) DB::table('products')
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->where('stock_status', '!=', 'out_of_stock')
            ->where('stock_quantity', '>', self::CRITICAL_STOCK_THRESHOLD)
            ->whereColumn('stock_quantity', '>', 'low_stock_threshold')
            ->when($filters->categoryId, fn($q) => $q->where('category_id', $filters->categoryId))
            ->when($filters->productId,  fn($q) => $q->where('id', $filters->productId))
            ->count();

        // ── Pending orders — explicit query against orders table ──────────────
        $pendingOrdersCount = (int) DB::table('orders')
            ->whereNull('deleted_at')
            ->where('status', 'pending')
            ->count();

        // ── Low-stock card dynamic color/icon ─────────────────────────────────

        $lowStockDescription = match (true) {
            $outOfStockCount > 0 => __('analytics.insights.out_of_stock_count', ['count' => $outOfStockCount]),
            $criticalCount > 0   => __('analytics.insights.critical_stock_count', ['count' => $criticalCount]),
            default              => __('analytics.insights.low_stock_desc'),
        };

        $lowStockColor = match (true) {
            $outOfStockCount > 0 => 'danger',
            $criticalCount > 0   => 'warning',
            $lowStockTotal > 0   => 'warning',
            default              => 'success',
        };

        $lowStockIcon = $outOfStockCount > 0
            ? Heroicon::ExclamationCircle
            : Heroicon::ExclamationTriangle;

        return [
            // ── Row 1: Core KPIs ──────────────────────────────────────────────

            Stat::make(__('analytics.kpis.total_revenue'), $format($metrics['total_revenue']))
                ->description(__('analytics.kpis.today') . ' ' . $format($metrics['revenue_today']))
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->color('success')
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1',
                ]),

            Stat::make(__('analytics.kpis.total_orders'), number_format($metrics['total_orders']))
                ->description($pendingLabel)
                ->descriptionIcon(Heroicon::ShoppingCart)
                ->color('warning')
                ->url(route('filament.admin.resources.orders.index'))
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1 cursor-pointer',
                ]),

            Stat::make(__('analytics.kpis.total_customers'), number_format($metrics['total_customers']))
                ->description(__('analytics.kpis.customers_desc'))
                ->descriptionIcon(Heroicon::Users)
                ->color('info')
                ->url(route('filament.admin.resources.customers.index'))
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1 cursor-pointer',
                ]),

            Stat::make(__('analytics.kpis.total_products'), number_format($metrics['total_products']))
                ->description(__('analytics.kpis.products_desc'))
                ->descriptionIcon(Heroicon::Cube)
                ->color('gray')
                ->url(route('filament.admin.resources.products.index'))
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1 cursor-pointer',
                ]),

            // ── Row 2: Secondary KPIs ─────────────────────────────────────────

            Stat::make(__('analytics.kpis.average_order_value'), $format($metrics['average_order_value']))
                ->description($aovLabel)
                ->descriptionIcon(Heroicon::Calculator)
                ->color('primary')
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1',
                ]),

            Stat::make(__('analytics.kpis.orders_today'), number_format($metrics['orders_today']))
                ->description(__('analytics.kpis.orders_today_desc'))
                ->descriptionIcon(Heroicon::CalendarDays)
                ->color('primary')
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1',
                ]),

            Stat::make(__('analytics.kpis.revenue_today'), $format($metrics['revenue_today']))
                ->description($this->growthLabel($metrics['total_revenue'], $metrics['revenue_today']))
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->color('success')
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1',
                ]),

            Stat::make(__('analytics.kpis.pending_orders'), number_format($pendingOrdersCount))
                ->description(__('analytics.kpis.pending_orders_desc'))
                ->descriptionIcon(Heroicon::Clock)
                ->color($pendingOrdersCount > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.orders.index', [
                    'tableFilters[status][value]' => 'pending',
                ]))
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1 cursor-pointer',
                ]),

            // ── Row 3: Inventory health ───────────────────────────────────────

            Stat::make(__('analytics.insights.low_stock_alerts'), number_format($lowStockTotal))
                ->description($lowStockDescription)
                ->descriptionIcon($lowStockIcon)
                ->color($lowStockColor)
                ->url(route('filament.admin.resources.products.index', [
                    'tableFilters[stock_status][value]' => 'low_stock',
                ]))
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1 cursor-pointer',
                ]),

            Stat::make(__('analytics.insights.out_of_stock'), number_format($outOfStockCount))
                ->description(__('analytics.insights.out_of_stock_desc'))
                ->descriptionIcon(Heroicon::NoSymbol)
                ->color($outOfStockCount > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.products.index', [
                    'tableFilters[stock_status][value]' => 'out_of_stock',
                ]))
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1 cursor-pointer',
                ]),

            Stat::make(__('analytics.insights.critical_stock'), number_format($criticalCount))
                ->description(__('analytics.insights.critical_stock_desc', ['threshold' => self::CRITICAL_STOCK_THRESHOLD]))
                ->descriptionIcon(Heroicon::ExclamationTriangle)
                ->color($criticalCount > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.products.index', [
                    'tableFilters[stock_status][value]' => 'low_stock',
                ]))
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1 cursor-pointer',
                ]),

            Stat::make(__('analytics.insights.healthy_stock'), number_format($healthyCount))
                ->description(__('analytics.insights.healthy_stock_desc'))
                ->descriptionIcon(Heroicon::CheckCircle)
                ->color('success')
                ->url(route('filament.admin.resources.products.index'))
                ->extraAttributes([
                    'class' => 'hover:shadow-lg transition-all duration-300 hover:-translate-y-1 cursor-pointer',
                ]),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function growthLabel(float $total, float $today): string
    {
        if ($total <= 0) {
            return __('analytics.kpis.no_data');
        }

        $percent = round(($today / $total) * 100, 1);

        return __('analytics.kpis.revenue_today_desc', ['percent' => $percent]);
    }
}
