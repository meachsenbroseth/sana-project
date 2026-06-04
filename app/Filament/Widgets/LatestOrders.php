<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrders extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()->latest()->limit(10))
            ->heading(__('table.latest_orders'))
            ->paginated(false)
            ->columns([
                TextColumn::make('order_number')
                    ->label(__('table.order_number'))
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('customer.name')
                    ->label(__('table.customer'))
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->url(fn ($record) => $record->customer
                            ? CustomerResource::getUrl('edit', ['record' => $record->customer])
                            : null
                    ),

                TextColumn::make('discount_amount')
                    ->label(__('table.discount'))
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('total')
                    ->label(__('table.total'))
                    ->money('USD')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label(__('table.payment_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'paid' => 'heroicon-m-check-circle',
                        'pending' => 'heroicon-m-clock',
                        'failed' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('table.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label(__('table.items'))
                    ->color('info')
                    ->badge(),

                TextColumn::make('tracking_number')
                    ->label(__('table.tracking_number'))
                    ->toggleable()
                    ->copyable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(__('table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label(__('table.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
