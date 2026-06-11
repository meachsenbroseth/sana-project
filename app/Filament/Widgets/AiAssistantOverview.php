<?php

namespace App\Filament\Widgets;

use App\Services\Ai\BusinessIntelligenceContextService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiAssistantOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $snapshot = app(BusinessIntelligenceContextService::class)->snapshot();

        return [
            Stat::make(__('analytics.ai_assistant.metrics.revenue_month'), '$' . number_format((float) data_get($snapshot, 'sales.revenue_this_month', 0), 2))
                ->description(__('analytics.ai_assistant.metrics.revenue_growth') . ': ' . number_format((float) data_get($snapshot, 'sales.revenue_growth_percent', 0), 1) . '%')
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->color('success'),
            Stat::make(__('analytics.ai_assistant.metrics.next_month'), '$' . number_format((float) data_get($snapshot, 'sales.next_month_revenue_estimate', 0), 2))
                ->description(__('analytics.ai_assistant.forecast_description'))
                ->descriptionIcon(Heroicon::ChartBar)
                ->color('info'),
            Stat::make(__('analytics.ai_assistant.metrics.low_stock'), number_format(count((array) data_get($snapshot, 'products.low_stock', []))))
                ->description(__('analytics.ai_assistant.low_stock_description'))
                ->descriptionIcon(Heroicon::ExclamationTriangle)
                ->color('warning'),
            Stat::make(__('analytics.ai_assistant.metrics.returning_customers'), number_format((int) data_get($snapshot, 'customers.returning_customers', 0)))
                ->description(__('analytics.ai_assistant.retention_description'))
                ->descriptionIcon(Heroicon::Users)
                ->color('primary'),
        ];
    }
}
