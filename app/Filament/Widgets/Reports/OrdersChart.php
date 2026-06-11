<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\TrendValue;

class OrdersChart extends ChartWidget
{
    use InteractsWithAnalytics;

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
                return __('order.status.'.$value->date, [], app()->getLocale()) !== 'order.status.'.$value->date
                    ? __('order.status.'.$value->date)
                    : ucfirst($value->date);
            }

            return $value->date;
        })->toArray();

        // Dynamic colors based on filter type
        $backgroundColors = $this->filter === 'status'
            ? $this->getStatusColors($data)
            : $this->getGradientColors($data);

        return [
            'datasets' => [
                [
                    'label' => __('analytics.charts.orders_label'),
                    'data' => $data->map(fn (TrendValue $value): mixed => $value->aggregate)->toArray(),
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $this->filter === 'status' ? array_map(fn($color) => str_replace('0.7', '1', $color), $backgroundColors) : '#1e40af',
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                    'barPercentage' => 0.7,
                    'categoryPercentage' => 0.8,
                    'hoverBackgroundColor' => $this->filter === 'status'
                        ? array_map(fn($color) => str_replace('0.7', '0.9', $color), $backgroundColors)
                        : '#3b82f6',
                    'hoverBorderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Get gradient colors for regular bar charts
     */
    protected function getGradientColors($data): array
    {
        $max = $data->max(fn (TrendValue $value) => $value->aggregate) ?: 1;

        return $data->map(function (TrendValue $value) use ($max) {
            $intensity = min(0.9, max(0.4, $value->aggregate / $max));

            // Blue gradient based on value intensity
            return "rgba(37, 99, 235, " . (0.4 + $intensity * 0.4) . ")";
        })->toArray();
    }

    /**
     * Get custom colors for status chart
     */
    protected function getStatusColors($data): array
    {
        $statusColors = [
            'pending' => 'rgba(245, 158, 11, 0.7)',    // Amber
            'processing' => 'rgba(59, 130, 246, 0.7)',  // Blue
            'completed' => 'rgba(16, 185, 129, 0.7)',   // Green
            'cancelled' => 'rgba(239, 68, 68, 0.7)',    // Red
            'refunded' => 'rgba(139, 92, 246, 0.7)',    // Purple
            'on_hold' => 'rgba(107, 114, 128, 0.7)',    // Gray
        ];

        return $data->map(function (TrendValue $value) use ($statusColors) {
            $status = $value->date;
            return $statusColors[$status] ?? 'rgba(107, 114, 128, 0.7)';
        })->toArray();
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'boxWidth' => 10,
                        'font' => [
                            'size' => 12,
                            'weight' => '500',
                        ],
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(17, 24, 39, 0.9)',
                    'titleColor' => '#f3f4f6',
                    'bodyColor' => '#d1d5db',
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'displayColors' => true,
                ],
                'datalabels' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                        'drawBorder' => false,
                        'lineWidth' => 1,
                    ],
                    'ticks' => [
                        'stepSize' => $this->calculateStepSize(),
                        'font' => [
                            'size' => 11,
                        ],
                        'callback' => 'function(value) { return value.toLocaleString(); }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => __('analytics.charts.number_of_orders'),
                        'font' => [
                            'size' => 11,
                            'weight' => '500',
                        ],
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'elements' => [
                'bar' => [
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                ],
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeInOutQuart',
                'animateScale' => true,
                'animateRotate' => true,
            ],
            'hover' => [
                'mode' => 'nearest',
                'intersect' => true,
                'animationDuration' => 200,
            ],
        ];
    }

    protected function calculateStepSize(): int
    {
        $data = $this->analytics()->ordersTrend($this->filters(), $this->filter ?? 'day');
        $max = $data->max(fn (TrendValue $value) => $value->aggregate) ?: 10;

        if ($max <= 50) return 10;
        if ($max <= 100) return 20;
        if ($max <= 500) return 50;
        if ($max <= 1000) return 100;
        return 200;
    }
}
