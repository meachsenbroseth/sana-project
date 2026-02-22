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
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()->latest()->limit(10))
            ->heading('Latest Orders')
            ->paginated(false)
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
                    ->url(fn ($record) =>
                        $record->customer
                            ? CustomerResource::getUrl('edit', ['record' => $record->customer])
                            : null
                    ),

                TextColumn::make('discount_amount')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('total')
                    ->money('USD')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('payment_status')
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
                    ->label('Items')
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
            ]);
    }
}
