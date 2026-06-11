<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class StatsOverview extends StatsOverviewWidget
{

    //     public static function canView(): bool
    // {
    //     return false;
    // }


    protected ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        // --- 1. GET THE STATIC TOTALS ---
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total');
        $todayRevenue = Order::where('payment_status', 'paid')->whereDate('created_at', today())->sum('total');
        $totalOrder = Order::count();
        $pendingOrder = Order::where('status', 'pending')->count();
        $totalCustomers = Customer::count();
        $thisMonthCustomer = Customer::whereMonth('created_at', now())->whereYear('created_at', now()->year)->count();
        $lowStock = Product::lowStock()->count();

        // --- 2. GENERATE DYNAMIC CHART DATA (Last 7 Days) ---

        // Revenue Chart (Summing the 'total' column)
        $revenueData = Trend::query(Order::where('payment_status', 'paid'))
            ->between(start: now()->subDays(6), end: now())
            ->perDay()
            ->sum('total')
            ->map(fn (TrendValue $value) => $value->aggregate)
            ->toArray();

        // Orders Chart (Counting the number of orders)
        $ordersData = Trend::model(Order::class)
            ->between(start: now()->subDays(6), end: now())
            ->perDay()
            ->count()
            ->map(fn (TrendValue $value) => $value->aggregate)
            ->toArray();

        // Customers Chart (Counting new registrations)
        $customersData = Trend::model(Customer::class)
            ->between(start: now()->subDays(6), end: now())
            ->perDay()
            ->count()
            ->map(fn (TrendValue $value) => $value->aggregate)
            ->toArray();


        // --- 3. RETURN THE STATS ---
        return [
            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('Today $' . number_format($todayRevenue, 2))
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->color('success')
                ->chart($revenueData), // 🔥 Completely Dynamic!

            Stat::make('Total Order', $totalOrder)
                ->description(number_format($pendingOrder) . ' pending')
                ->descriptionIcon(Heroicon::ShoppingCart)
                ->color('warning')
                ->url(route('filament.admin.resources.orders.index'))
                ->chart($ordersData), // 🔥 Completely Dynamic!

            Stat::make('Total Customers', $totalCustomers)
                ->description(number_format($thisMonthCustomer) . ' new this month')
                ->descriptionIcon(Heroicon::Users)
                ->url(route('filament.admin.resources.customers.index'))
                ->color('info')
                ->chart($customersData), // 🔥 Completely Dynamic!

            Stat::make('Low Stock Alerts', $lowStock)
                ->description('Products running low')
                ->descriptionIcon(Heroicon::ExclamationTriangle)
                ->color('danger')
                ->url(route('filament.admin.resources.products.index')),
        ];
    }
}
