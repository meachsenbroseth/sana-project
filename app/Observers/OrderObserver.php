<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Filament\Resources\Orders\OrderResource;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmation;

use Filament\Notifications\Notification;
use Filament\Notifications\Actions\NotificationAction;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // 1. Log the initial status history
        $order->statusHistories()->create([
            'status' => $order->status,
            'notes' => 'Order created',
        ]);

        // 2. Send the confirmation email to the customer
        if ($order->customer && $order->customer->email) {
            Mail::to($order->customer->email)->queue(new OrderConfirmation($order));
        }

        // 3. Notify the Admins in Filament
        $admins = User::all();

Notification::make()
    ->title('New Order Received! 🚀')
    ->body("Order #{$order->order_number} has been placed for $" . number_format($order->total, 2) . ".")
    ->icon('heroicon-o-shopping-bag')
    ->success()
    // ->actions([
    //     NotificationAction::make('viewOrder')
    //         ->label('View Order')
    //         ->button()
    //         ->url(fn () => OrderResource::getUrl(
    //             'edit',
    //             ['record' => $order->getKey()]
    //         )),
    // ])
    ->sendToDatabase($admins);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
