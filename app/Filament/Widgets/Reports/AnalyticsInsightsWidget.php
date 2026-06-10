<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsInsightsWidget extends StatsOverviewWidget
{
    use InteractsWithAnalytics;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    public function getHeading(): ?string
    {
        return __('analytics.widgets.insights_heading');
    }

    protected function getStats(): array
    {
        $insights = $this->analytics()->insightMetrics($this->filters());
        $format = fn (float $amount): string => $this->analytics()->formatCurrency($amount);

        return [
            Stat::make(__('analytics.insights.best_selling_product'), $insights['best_selling_product'] ?? __('analytics.empty_state'))
                ->descriptionIcon(Heroicon::Trophy)
                ->color('success'),
            Stat::make(__('analytics.insights.most_active_customer'), $insights['most_active_customer'] ?? __('analytics.empty_state'))
                ->descriptionIcon(Heroicon::UserCircle)
                ->color('info'),
            Stat::make(__('analytics.insights.highest_revenue_day'), $insights['highest_revenue_day'] ?? __('analytics.empty_state'))
                ->description($insights['highest_revenue_day'] ? $format($insights['highest_revenue_day_amount']) : null)
                ->descriptionIcon(Heroicon::Calendar)
                ->color('warning'),
            Stat::make(__('analytics.insights.highest_revenue_month'), $insights['highest_revenue_month'] ?? __('analytics.empty_state'))
                ->description($insights['highest_revenue_month'] ? $format($insights['highest_revenue_month_amount']) : null)
                ->descriptionIcon(Heroicon::ChartBar)
                ->color('primary'),
            Stat::make(__('analytics.insights.most_popular_category'), $insights['most_popular_category'] ?? __('analytics.empty_state'))
                ->descriptionIcon(Heroicon::Tag)
                ->color('gray'),
            Stat::make(__('analytics.insights.average_clv'), $format($insights['average_customer_lifetime_value']))
                ->descriptionIcon(Heroicon::CurrencyDollar)
                ->color('success'),
        ];
    }
}
