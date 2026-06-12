<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OrderReportTable extends TableWidget
{
    use InteractsWithAnalytics;

    protected static bool $isDiscovered = false;


    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): ?string
    {
        return __('analytics.tables.order_report');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->analytics()->orderReportQuery($this->filters()))
            ->columns([
                TextColumn::make('order_number')
                    ->label(__('analytics.columns.order_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label(__('analytics.columns.customer'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('analytics.columns.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('order.status.'.$state, [], app()->getLocale()) !== 'order.status.'.$state
                        ? __('order.status.'.$state)
                        : ucfirst($state)),
                TextColumn::make('payment_method')
                    ->label(__('analytics.columns.payment_method'))
                    ->formatStateUsing(fn (string $state): string => __('analytics.payment_methods.'.$state, [], app()->getLocale()) !== 'analytics.payment_methods.'.$state
                        ? __('analytics.payment_methods.'.$state)
                        : $state),
                TextColumn::make('total')
                    ->label(__('analytics.columns.total'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('analytics.columns.created_date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading(__('analytics.empty_state'))
            ->emptyStateDescription(__('analytics.empty_state_description'));
    }
}
