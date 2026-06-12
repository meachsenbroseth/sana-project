<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\TrendValue;

class CustomerGrowthChart extends ChartWidget
{
    use InteractsWithAnalytics;

    public static function canView(): bool
    {
        return auth()->user()?->can('View:CustomerGrowthChart') ?? false;
    }

    protected static bool $isDiscovered = false;


    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public ?string $filter = 'day';

    public function getHeading(): ?string
    {
        return __('analytics.charts.customer_growth');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => __('analytics.charts.groupings.per_day'),
            'month' => __('analytics.charts.groupings.per_month'),
            'trend' => __('analytics.charts.groupings.registration_trend'),
        ];
    }

    protected function getData(): array
    {
        $grouping = $this->filter === 'month' ? 'month' : 'day';
        $data = $this->analytics()->customerGrowthTrend($this->filters(), $grouping);

        return [
            'datasets' => [
                [
                    'label' => __('analytics.charts.customers_label'),
                    'data' => $data->map(fn(TrendValue $value): mixed => $value->aggregate)->toArray(),
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(5, 150, 105, 0.1)',
                    'fill' => $this->filter === 'trend',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $data->map(fn(TrendValue $value): string => $value->date)->toArray(),
        ];
    }
}
