<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
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
                    ->url(fn ($record) => $record->customer ? CustomerResource::getUrl('edit', ['record' => $record->customer]) : null),

                TextColumn::make('discount_amount')
                    ->label(__('table.discount'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->searchable()
                    ->action(
                        Action::make('markPaidBadge')
                            ->label(__('order.actions.mark_paid'))
                            ->requiresConfirmation()
                            ->modalHeading(__('order.actions.mark_paid'))
                            ->modalDescription(__('order.actions.mark_paid_confirm'))
                            ->visible(fn ($record) => $record->payment_status !== 'paid')
                            ->action(fn ($record) => self::performMarkPaid($record))
                    ),

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
                    })
                    ->action(
                        Action::make('advanceStatusBadge')
                            ->label(fn ($record) => __('order.actions.advance_to', [
                                'status' => __('order.status.'.self::nextStatus($record->status)),
                            ]))
                            ->requiresConfirmation()
                            ->modalHeading(fn ($record) => self::advanceStatusModalHeading($record->status))
                            ->modalDescription(fn ($record) => self::advanceStatusModalDescription($record->status))
                            ->visible(fn ($record) => self::nextStatus($record->status) !== null && $record->status !== 'cancelled')
                            ->action(fn ($record) => self::performAdvanceStatus($record))
                    ),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label(__('order.items'))
                    ->color('info')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tracking_number')
                    ->label(__('table.tracking_number'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable()
                    ->searchable(),

                // --- DONE TRACKING COLUMNS ---
                TextColumn::make('doneBy.name')
                    ->label(__('table.done_by'))
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('done_at')
                    ->label(__('table.done_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),

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
                    ->native(false),

                SelectFilter::make('payment_status')
                    ->label(__('order.payment_status_label'))
                    ->options([
                        'pending' => __('order.payment_status.pending'),
                        'paid' => __('order.payment_status.paid'),
                        'failed' => __('order.payment_status.failed'),
                    ])
                    ->native(false)
                    ->indicator(__('order.payment')),

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
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->color('info'),
                    EditAction::make()
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning'),
                    Action::make('markDone')
                        ->label(__('order.actions.mark_done'))
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->done_at === null)
                        ->action(function ($record) {
                            $record->update([
                                'done_at' => now(),
                                'done_by' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title(__('order.notifications.marked_done'))
                                ->success()
                                ->send();
                        }),
                    Action::make('unmarkDone')
                        ->label(__('order.actions.unmark_done'))
                        ->icon('heroicon-m-x-circle')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->done_at !== null)
                        ->action(function ($record) {
                            $record->update([
                                'done_at' => null,
                                'done_by' => null,
                            ]);

                            Notification::make()
                                ->title(__('order.notifications.unmarked_done'))
                                ->warning()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->icon('heroicon-m-trash')
                        ->color('danger'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Returns the next status in the fulfilment pipeline, or null when
     * there is no further step (delivered / cancelled).
     */
    private static function nextStatus(string $current): ?string
    {
        return match ($current) {
            'pending' => 'processing',
            'processing' => 'shipped',
            'shipped' => 'delivered',
            default => null,
        };
    }

    /**
     * Builds the confirmation modal heading for an advance-status action.
     */
    private static function advanceStatusModalHeading(string $currentStatus): string
    {
        return __('order.actions.advance_to', [
            'status' => __('order.status.'.self::nextStatus($currentStatus)),
        ]);
    }

    /**
     * Builds the confirmation modal body for an advance-status action.
     */
    private static function advanceStatusModalDescription(string $currentStatus): string
    {
        return __('order.actions.advance_status_confirm', [
            'status' => __('order.status.'.self::nextStatus($currentStatus)),
        ]);
    }

    /**
     * Advances the order to the next status and fires the success notification.
     */
    private static function performAdvanceStatus(mixed $record): void
    {
        $next = self::nextStatus($record->status);

        if ($next) {
            $record->update(['status' => $next]);

            Notification::make()
                ->title(__('order.notifications.status_advanced', [
                    'status' => __('order.status.'.$next),
                ]))
                ->success()
                ->send();
        }
    }

    /**
     * Marks the order as paid and fires the success notification.
     */
    private static function performMarkPaid(mixed $record): void
    {
        $record->update(['payment_status' => 'paid']);

        Notification::make()
            ->title(__('order.notifications.marked_paid'))
            ->success()
            ->send();
    }
}
