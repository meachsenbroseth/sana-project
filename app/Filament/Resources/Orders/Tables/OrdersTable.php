<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->url(fn ($record) => $record->customer ? CustomerResource::getUrl('edit', ['record' => $record->customer]) : null),

                TextColumn::make('discount_amount')
                    ->money('USD') // UPGRADE: Formatted as money to match the total column
                    ->sortable(),

                TextColumn::make('total')
                    ->money('USD')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->badge()
                    // UPGRADE: Added colors so you can identify payment status at a glance
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    // UPGRADE: Added icons for extra visual polish
                    ->icon(fn (string $state): string => match ($state) {
                        'paid' => 'heroicon-m-check-circle',
                        'pending' => 'heroicon-m-clock',
                        'failed' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    // UPGRADE: Added colors for the fulfillment status
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
                    ->label(__('order.items'))
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('order.status_label'))
                    ->options([
                        'pending' => __('order.status.pending'),
                        'processing' => __('order.status.processing'),
                        'shipped' => __('order.status.shipped'),
                        'delivered' => __('order.status.delivered'),
                        'cancelled' => __('order.status.cancelled'),
                    ])
                    ->native(false) // Keeps the modern search box style
                    ->indicator(__('order.status')),

                // 2. Payment Status Filter (Updated)
                SelectFilter::make('payment_status')
                    ->label(__('order.payment_status_label'))
                    ->options([
                        'pending' => __('order.payment_status.pending'),
                        'paid' => __('order.payment_status.paid'),
                        'failed' => __('order.payment_status.failed'),
                    ])
                    ->native(false)
                    ->indicator(__('order.payment')),

                // 3. (Optional Bonus) Filter by Date
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label(__('order.order_date_from')),
                        DatePicker::make('created_until')->label(__('order.order_date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

            ])
            ->recordActions([
                ViewAction::make()
                    ->button()
                    ->color('info'),
                DeleteAction::make()
                    ->button()
                    ->color('danger'),
                EditAction::make()
                    ->button()
                    ->color('warning'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
