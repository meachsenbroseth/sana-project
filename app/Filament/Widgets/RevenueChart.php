<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected ?string $heading = 'Revenue Chart';
    public ?string $filter = 'week';

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        // 1. Only grab orders that have actually been paid!
        $query = Order::where('payment_status', 'paid');

        // 2. Dynamically change the timeframe AND the grouping (days vs months)
        $data = match ($activeFilter) {
            'week' => Trend::query($query)->between(now()->subWeek(), now())->perDay()->sum('total'),
            'month' => Trend::query($query)->between(now()->subMonth(), now())->perDay()->sum('total'),
            'year' => Trend::query($query)->between(now()->subYear(), now())->perMonth()->sum('total'),
        };

        return [
            // 3. FIXED: Changed 'dataset' to 'datasets'
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate)->toArray(),
                    // Optional: Make the chart line match your theme's primary blue!
                    'borderColor' => '#1d398f',
                    'backgroundColor' => '#1d398f', 
                ],
            ],
            'labels' => $data->map(fn(TrendValue $value) => $value->date)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): array|null
    {
        return [
            'week' => 'Last Week',
            'month' => 'Last Month',
            'year' => 'Last Year',
        ];
    }
}