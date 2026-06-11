<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;

class AnalyticsStatsOverview extends StatsOverviewWidget
{
    use InteractsWithAnalytics;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

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

        $start = $filters->startDate ?? now()->subDays(6);
        $end   = $filters->endDate   ?? now();

        // ── Trend charts (7-point sparklines) ────────────────────────────────

        $revenueChart = Trend::query($this->analytics()->paidOrderQuery($filters))
            ->between($start, $end)
            ->perDay()
            ->sum('total')
            ->map(fn(TrendValue $v) => $v->aggregate)
            ->toArray();

        $ordersChart = Trend::query($this->analytics()->orderQuery($filters))
            ->between($start, $end)
            ->perDay()
            ->count()
            ->map(fn(TrendValue $v) => $v->aggregate)
            ->toArray();

        $customersChart = Trend::model(Customer::class)
            ->between($start, $end)
            ->perDay()
            ->count()
            ->map(fn(TrendValue $v) => $v->aggregate)
            ->toArray();

        // ── Growth / context labels ───────────────────────────────────────────

        $revenueGrowthLabel = $this->growthLabel($metrics['total_revenue'], $metrics['revenue_today']);
        $pendingLabel       = number_format($metrics['pending_orders']) . ' ' . __('analytics.kpis.pending');
        $aovLabel           = __('analytics.kpis.aov_desc', ['value' => $format($metrics['average_order_value'])]);

        return [
            // ── Row 1 ─────────────────────────────────────────────────────────

            Stat::make(__('analytics.kpis.total_revenue'), $format($metrics['total_revenue']))
                ->description(__('analytics.kpis.today') . ' ' . $format($metrics['revenue_today']))
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->color('success')
                ->chart($revenueChart),

            Stat::make(__('analytics.kpis.total_orders'), number_format($metrics['total_orders']))
                ->description($pendingLabel)
                ->descriptionIcon(Heroicon::ShoppingCart)
                ->color('warning')
                ->chart($ordersChart)
                ->url(route('filament.admin.resources.orders.index')),

            Stat::make(__('analytics.kpis.total_customers'), number_format($metrics['total_customers']))
                ->description(__('analytics.kpis.customers_desc'))
                ->descriptionIcon(Heroicon::Users)
                ->color('info')
                ->chart($customersChart)
                ->url(route('filament.admin.resources.customers.index')),

            Stat::make(__('analytics.kpis.total_products'), number_format($metrics['total_products']))
                ->description(__('analytics.kpis.products_desc'))
                ->descriptionIcon(Heroicon::Cube)
                ->color('gray')
                ->url(route('filament.admin.resources.products.index')),

            // ── Row 2 ─────────────────────────────────────────────────────────

            Stat::make(__('analytics.kpis.average_order_value'), $format($metrics['average_order_value']))
                ->description($aovLabel)
                ->descriptionIcon(Heroicon::Calculator)
                ->color('primary'),

            Stat::make(__('analytics.kpis.orders_today'), number_format($metrics['orders_today']))
                ->description(__('analytics.kpis.orders_today_desc'))
                ->descriptionIcon(Heroicon::CalendarDays)
                ->color('primary'),

            Stat::make(__('analytics.kpis.revenue_today'), $format($metrics['revenue_today']))
                ->description($revenueGrowthLabel)
                // ->descriptionIcon(Heroicon::BankNotes)
                ->color('success'),

            Stat::make(__('analytics.kpis.pending_orders'), number_format($metrics['pending_orders']))
                ->description(__('analytics.kpis.pending_orders_desc'))
                ->descriptionIcon(Heroicon::Clock)
                ->color('danger')
                ->url(route('filament.admin.resources.orders.index', ['tableFilters[status][value]' => 'pending'])),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns a human-readable growth label comparing today's revenue to the
     * all-time total (a simple "today vs period" indicator for the sparkline).
     */
    protected function growthLabel(float $total, float $today): string
    {
        if ($total <= 0) {
            return __('analytics.kpis.no_data');
        }

        $percent = round(($today / $total) * 100, 1);

        return __('analytics.kpis.revenue_today_desc', ['percent' => $percent]);
    }
}
