<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\TrendValue;

class SalesRevenueChart extends ChartWidget
{
    use InteractsWithAnalytics;

    public static function canView(): bool
    {
        return auth()->user()?->can('View:SalesRevenueChart') ?? false;
    }


    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public ?string $filter = 'daily';

    public function getHeading(): ?string
    {
        return __('analytics.charts.sales_revenue');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'daily' => __('analytics.charts.periods.daily'),
            'weekly' => __('analytics.charts.periods.weekly'),
            'monthly' => __('analytics.charts.periods.monthly'),
            'yearly' => __('analytics.charts.periods.yearly'),
        ];
    }

    protected function getData(): array
    {
        $period = match ($this->filter) {
            'weekly' => 'weekly',
            'monthly' => 'monthly',
            'yearly' => 'yearly',
            default => 'daily',
        };

        $data = $this->analytics()->revenueTrend($this->filters(), $period);

        return [
            'datasets' => [
                [
                    'label' => __('analytics.charts.revenue_label'),
                    'data' => $data->map(fn(TrendValue $value): mixed => $value->aggregate)->toArray(),
                    'borderColor' => '#1d398f',
                    'backgroundColor' => 'rgba(29, 57, 143, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $data->map(fn(TrendValue $value): string => $value->date)->toArray(),
        ];
    }
}
