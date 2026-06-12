<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Widgets\ChartWidget;

class ProductPerformanceChart extends ChartWidget
{
    use InteractsWithAnalytics;

    public static function canView(): bool
    {
        return auth()->user()?->can('View:ProductPerformanceChart') ?? false;
    }

    protected static bool $isDiscovered = false;


    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    public ?string $filter = 'top_selling';

    public function getHeading(): ?string
    {
        return __('analytics.charts.product_performance');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            'top_selling' => __('analytics.charts.metrics.top_selling'),
            'most_viewed' => __('analytics.charts.metrics.most_viewed'),
            'highest_revenue' => __('analytics.charts.metrics.highest_revenue'),
            'low_stock' => __('analytics.charts.metrics.low_stock'),
        ];
    }

    protected function getData(): array
    {
        if (! $this->productsAvailable()) {
            return [
                'datasets' => [
                    [
                        'label' => __('analytics.charts.product_performance'),
                        'data' => [],
                        'backgroundColor' => '#7c3aed',
                    ],
                ],
                'labels' => [__('analytics.unavailable.heading')],
            ];
        }

        $metric = match ($this->filter) {
            'most_viewed' => 'most_viewed',
            'highest_revenue' => 'highest_revenue',
            'low_stock' => 'low_stock',
            default => 'top_selling',
        };

        $records = $this->analytics()->productPerformance($this->filters(), $metric);

        $labels = $records->map(function ($record): string {
            return $record->name ?? $record->product_name ?? __('analytics.empty_state');
        })->toArray();

        $values = $records->map(function ($record) use ($metric): float|int {
            return match ($metric) {
                'most_viewed' => (int) ($record->view_count ?? 0),
                'highest_revenue' => (float) ($record->revenue ?? 0),
                'low_stock' => (int) ($record->stock_quantity ?? 0),
                default => (int) ($record->quantity_sold ?? 0),
            };
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => __('analytics.charts.product_performance'),
                    'data' => $values,
                    'backgroundColor' => '#7c3aed',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
