<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Order;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LatestOrders extends TableWidget
{
    protected int  | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query())
            ->columns([
                TextColumn::make('order_number')
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->url(fn($record) => $record->customer ? CustomerResource::getUrl('edit', [$record->customer]) : null),
                TextColumn::make('discount_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('USD')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->color('info')
                    ->badge(),
                TextColumn::make('tracking_number')
                    ->toggleable()
                    ->copyable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->heading('Latest Order')
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
