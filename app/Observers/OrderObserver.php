<?php

namespace App\Observers;

use App\Filament\Resources\Orders\OrderResource;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Filament\Notifications\Actions\NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            ->body("Order #{$order->order_number} has been placed for $" . number_format($order->total, 2) . '.')
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

        //send to telegram
        Http::withoutVerifying()->post(
            'https://api.telegram.org/bot' . config('services.telegram.bot_token') . '/sendMessage',
            [
                'chat_id' => config('services.telegram.chat_id'),
                'parse_mode' => 'HTML',
                'text' => implode("\n", [
                    '<b>NEW ORDER</b>  #' . $order->order_number,
                    '─────────────────────────',
                    '',
                    '<b>CUSTOMER</b>',
                    '  Name     ' . ($order->customer?->name ?? 'Guest'),
                    '  Email    ' . ($order->customer?->email ?? 'N/A'),
                    '  Phone    ' . ($order->shipping_phone ?? 'N/A'),
                    '',
                    '<b>ORDER</b>',
                    '  Status   ' . $order->status,
                    '  Payment  ' . $order->payment_method,
                    '  Total    $' . number_format($order->total, 2),
                    '  Shipping ' . ($order->shipping_method ?? 'Standard'),
                    '',
                    '<b>ADDRESS</b>',
                    '  ' . $order->shipping_address_line_1,
                    ($order->shipping_address_line_2 ? '  ' . $order->shipping_address_line_2 : null),
                    '  ' . implode(', ', array_filter([$order->shipping_city, $order->shipping_state])),
                    '  ' . $order->shipping_country,
                    '',
                    '<b>NOTE</b>',
                    '  ' . ($order->customer_notes ?? 'No notes'),
                    '',
                    '─────────────────────────',
                    $order->created_at->format('d M Y  h:i A'),
                ]),
            ]
        );
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $originalStatus = (string) $order->getOriginal('status');
        $newStatus = (string) $order->status;
        $alreadyProcessed = $order->statusHistories()
            ->where('status', 'processing')
            ->exists();

        $order->statusHistories()->create([
            'status' => $newStatus,
            'notes' => "Status changed from {$originalStatus} to {$newStatus}.",
        ]);

        if ($newStatus !== 'processing') {
            return;
        }

        if ($originalStatus === 'processing') {
            return;
        }

        if ($alreadyProcessed) {
            Log::warning('Skipped stock deduction for order that was already processed before.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return;
        }

        DB::transaction(function () use ($order): void {
            $items = OrderItem::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    Log::warning('Skipped stock update because order item product no longer exists.', [
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                    ]);

                    continue;
                }

                $previousStock = (int) $product->stock_quantity;
                $requestedQuantity = max((int) $item->quantity, 0);
                $newStock = $previousStock > 0
                    ? max($previousStock - $requestedQuantity, 0)
                    : 0;
                $newStockStatus = $newStock > 0 ? 'in_stock' : 'out_of_stock';
                $isLowStock = $newStock <= (int) $product->low_stock_threshold;

                $product->update([
                    'stock_quantity' => $newStock,
                    'stock_status' => $newStockStatus,
                ]);

                Log::info('Product stock updated after order moved to processing.', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_item_id' => $item->id,
                    'product_id' => $product->id,
                    'previous_stock_quantity' => $previousStock,
                    'deducted_quantity' => $requestedQuantity,
                    'new_stock_quantity' => $newStock,
                    'new_stock_status' => $newStockStatus,
                    'is_low_stock' => $isLowStock,
                ]);
            }
        });
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
