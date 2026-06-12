<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CustomerReportTable extends TableWidget
{
    use InteractsWithAnalytics;

    public static function canView(): bool
    {
        return auth()->user()?->can('View:CustomerReportTable') ?? false;
    }


    protected static bool $isDiscovered = false;


    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): ?string
    {
        return __('analytics.tables.customer_report');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => $this->analytics()->customerReportQuery($this->filters()))
            ->columns([
                TextColumn::make('name')
                    ->label(__('analytics.columns.customer_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('analytics.columns.email'))
                    ->searchable(),
                TextColumn::make('total_orders')
                    ->label(__('analytics.columns.total_orders'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_spent')
                    ->label(__('analytics.columns.total_spent'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('last_order_date')
                    ->label(__('analytics.columns.last_order_date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('total_spent', 'desc')
            ->defaultKeySort(false)
            ->paginated([10, 25, 50])
            ->emptyStateHeading(__('analytics.empty_state'))
            ->emptyStateDescription(__('analytics.empty_state_description'));
    }
}
