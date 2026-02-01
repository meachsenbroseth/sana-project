<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '10s';
    protected function getStats(): array
    {
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total');
        
        $todayRevenue = Order::where('payment_status', 'paid')
        ->whereDate('created_at', today())
        ->sum('total');
        
        $totalOrder = Order::count();
        
        $pendingOrder = Order::where('status', 'pending')->count();
        
        $totalCustomers = Customer::count();
        
        $thisMonthCustomer = Customer::wheremonth('created_at', now())
        ->whereYear('created_at', now()->year)
        ->count();
       $lowStock = Product::lowStock()->count();
        return [
            Stat::make('Total Revenue', '$'.number_format($totalRevenue,2))
                ->description('Today $'. number_format($todayRevenue,2))
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->color('success'),
            Stat::make('Total Order', $totalOrder)
                ->description(number_format($pendingOrder). ' pending')
                ->descriptionIcon(Heroicon::ShoppingCart)
                ->color('warning')
                ->url(route('filament.admin.resources.orders.index')),
            Stat::make('Total Customers', $totalCustomers)
                ->description(number_format($thisMonthCustomer). ' new this mouth')
                ->descriptionIcon(Heroicon::Users)
                ->url(route('filament.admin.resources.customers.index'))
                ->color('info'),
            Stat::make('Low Stock Alerts',$lowStock)
                ->description('Products running low')
                ->descriptionIcon(Heroicon::ExclamationTriangle)
                ->color('danger')
                ->url(route('filament.admin.resources.products.index')),
        ];
    }
}
