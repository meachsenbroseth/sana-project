<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markDone')
                ->label(__('order.actions.mark_done'))
                ->icon('heroicon-m-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->done_at === null)
                ->action(function () {
                    $this->record->update([
                        'done_at' => now(),
                        'done_by' => auth()->id(),
                    ]);

                    $this->refreshFormData(['done_at', 'done_by']);

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
                ->visible(fn () => $this->record->done_at !== null)
                ->action(function () {
                    $this->record->update([
                        'done_at' => null,
                        'done_by' => null,
                    ]);

                    $this->refreshFormData(['done_at', 'done_by']);

                    Notification::make()
                        ->title(__('order.notifications.unmarked_done'))
                        ->warning()
                        ->send();
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
