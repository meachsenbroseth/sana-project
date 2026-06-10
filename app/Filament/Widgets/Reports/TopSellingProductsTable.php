<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Widgets\Reports\Concerns\InteractsWithAnalytics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopSellingProductsTable extends TableWidget
{
    use InteractsWithAnalytics;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): ?string
    {
        return __('analytics.tables.top_selling_products');
    }

    public function table(Table $table): Table
    {
        $unavailable = ! $this->analytics()->productsAvailable();

        return $table
            ->query(fn (): Builder => $this->analytics()->topSellingProductsEloquentQuery($this->filters()))
            ->columns([
                TextColumn::make('product_name')
                    ->label(__('analytics.columns.product_name'))
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('product_name', $direction);
                    }),
                TextColumn::make('product_sku')
                    ->label(__('analytics.columns.sku'))
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('product_sku', $direction);
                    }),
                TextColumn::make('quantity_sold')
                    ->label(__('analytics.columns.quantity_sold'))
                    ->numeric()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('quantity_sold', $direction);
                    }),
                TextColumn::make('revenue')
                    ->label(__('analytics.columns.revenue'))
                    ->money('USD')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('revenue', $direction);
                    }),
                TextColumn::make('stock_remaining')
                    ->label(__('analytics.columns.stock_remaining'))
                    ->numeric()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('stock_remaining', $direction);
                    }),
            ])
            ->defaultSort('quantity_sold', 'desc')
            ->defaultKeySort(false)
            ->recordTitleAttribute('product_name')
            ->paginated([10, 25, 50])
            ->emptyStateHeading($unavailable ? __('analytics.unavailable.heading') : __('analytics.empty_state'))
            ->emptyStateDescription($unavailable ? __('analytics.unavailable.products_description') : __('analytics.empty_state_description'));
    }
}
