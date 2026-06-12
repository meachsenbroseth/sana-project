<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\TrendValue;

class OrdersChart extends ChartWidget
{
    use InteractsWithAnalytics;

    public static function canView(): bool
    {
        return auth()->user()?->can('View:OrdersChart') ?? false;
    }

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public ?string $filter = 'day';

    public function getHeading(): ?string
    {
        return __('analytics.charts.orders');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            'day' => __('analytics.charts.groupings.by_day'),
            'month' => __('analytics.charts.groupings.by_month'),
            'status' => __('analytics.charts.groupings.by_status'),
        ];
    }

    protected function getData(): array
    {
        $data = $this->analytics()->ordersTrend($this->filters(), $this->filter ?? 'day');

        $labels = $data->map(function (TrendValue $value): string {
            if ($this->filter === 'status') {
                return __('order.status.' . $value->date, [], app()->getLocale()) !== 'order.status.' . $value->date
                    ? __('order.status.' . $value->date)
                    : ucfirst($value->date);
            }

            return $value->date;
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => __('analytics.charts.orders_label'),
                    'data' => $data->map(fn(TrendValue $value): mixed => $value->aggregate)->toArray(),
                    'backgroundColor' => '#2563eb',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
