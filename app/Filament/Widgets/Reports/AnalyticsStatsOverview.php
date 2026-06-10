<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

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
        $format = fn (float $amount): string => $this->analytics()->formatCurrency($amount);

        return [
            Stat::make(__('analytics.kpis.total_revenue'), $format($metrics['total_revenue']))
                ->descriptionIcon(Heroicon::Banknotes)
                ->color('success'),
            Stat::make(__('analytics.kpis.total_orders'), number_format($metrics['total_orders']))
                ->descriptionIcon(Heroicon::ShoppingCart)
                ->color('primary'),
            Stat::make(__('analytics.kpis.total_customers'), number_format($metrics['total_customers']))
                ->descriptionIcon(Heroicon::Users)
                ->color('info'),
            Stat::make(__('analytics.kpis.total_products'), number_format($metrics['total_products']))
                ->descriptionIcon(Heroicon::Cube)
                ->color('gray'),
            Stat::make(__('analytics.kpis.average_order_value'), $format($metrics['average_order_value']))
                ->descriptionIcon(Heroicon::Calculator)
                ->color('warning'),
            Stat::make(__('analytics.kpis.orders_today'), number_format($metrics['orders_today']))
                ->descriptionIcon(Heroicon::CalendarDays)
                ->color('primary'),
            Stat::make(__('analytics.kpis.revenue_today'), $format($metrics['revenue_today']))
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->color('success'),
            Stat::make(__('analytics.kpis.pending_orders'), number_format($metrics['pending_orders']))
                ->descriptionIcon(Heroicon::Clock)
                ->color('danger'),
        ];
    }
}
