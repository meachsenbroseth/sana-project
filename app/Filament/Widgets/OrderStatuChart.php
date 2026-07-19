<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatuChart extends ChartWidget
{
    protected static ?int $sort = 3; // Places it right after your Revenue Chart
    protected ?string $heading = 'Order Status Overview';

    public static function canView(): bool
    {
        return auth()->user()?->can('View:' . class_basename(static::class)) ?? false;
    }

    protected function getData(): array
    {
        // 1. Count the number of orders in each specific status
        $data = [
            'Pending' => Order::where('status', 'pending')->count(),
            'Processing' => Order::where('status', 'processing')->count(),
            'Shipped' => Order::where('status', 'shipped')->count(),
            'Delivered' => Order::where('status', 'delivered')->count(),
            'Cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        // 2. Map the data and colors to the Pie Chart
        return [
            'datasets' => [
                [
                    'label' => 'Order Statuses',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#eab308', // Yellow for Pending
                        '#3b82f6', // Blue for Processing
                        '#8b5cf6', // Purple for Shipped
                        '#22c55e', // Green for Delivered
                        '#ef4444', // Red for Cancelled
                    ],
                    // 'borderColor' => '#1d398f', // Optional: Adds your dark blue brand border to the slices
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
