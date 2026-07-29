<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class InteractionInsightsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('View:'.class_basename(static::class)) ?? false;
    }

    public function getHeading(): ?string
    {
        return 'Interaction Insights';
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $topProducts = DB::table('user_interactions')
            ->select('products.name', DB::raw('COUNT(*) as interaction_count'), DB::raw('SUM(user_interactions.weight) as total_weight'))
            ->join('products', 'products.id', '=', 'user_interactions.product_id')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_weight')
            ->limit(5)
            ->get();

        $eventCounts = DB::table('user_interactions')
            ->select('event_type', DB::raw('COUNT(*) as event_count'))
            ->groupBy('event_type')
            ->orderByDesc('event_count')
            ->get();

        $totalInteractions = DB::table('user_interactions')->count();

        $topProductName = $topProducts->first()?->name ?? 'No data';
        $topProductWeight = $topProducts->first() ? number_format($topProducts->first()->total_weight, 1) : '0';

        $topProductsList = $topProducts->take(3)->map(
            fn ($p) => "{$p->name} ({$p->interaction_count})"
        )->implode(', ');

        $eventBreakdown = $eventCounts->map(
            fn ($e) => "{$e->event_type}: {$e->event_count}"
        )->implode(', ');

        return [
            Stat::make('Total Interactions', number_format($totalInteractions))
                ->description($eventBreakdown ?: 'No interactions yet')
                ->descriptionIcon(Heroicon::CursorArrowRays)
                ->color('primary'),

            Stat::make('Most Engaged Product', $topProductName)
                ->description("Weighted score: {$topProductWeight}")
                ->descriptionIcon(Heroicon::Fire)
                ->color('success'),

            Stat::make('Top Products', $topProductsList ?: 'No data')
                ->description('By interaction count')
                ->descriptionIcon(Heroicon::ChartBar)
                ->color('warning'),
        ];
    }
}
