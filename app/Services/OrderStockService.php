<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderStockService
{
    public function deductForPaidOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->payment_status !== 'paid') {
                Log::info('Skipped stock deduction because order is not paid.', [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'payment_status' => $lockedOrder->payment_status,
                ]);

                return;
            }

            if ($lockedOrder->stock_deducted_at !== null) {
                Log::info('Skipped stock deduction because stock was already deducted for this order.', [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'stock_deducted_at' => $lockedOrder->stock_deducted_at,
                ]);

                return;
            }

            $items = $lockedOrder->items()
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    $this->failStockDeduction($lockedOrder, "Product for order item {$item->id} no longer exists.", [
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                    ]);
                }

                if (! $product->manage_stock) {
                    Log::info('Skipped stock deduction for unmanaged stock product.', [
                        'order_id' => $lockedOrder->id,
                        'order_number' => $lockedOrder->order_number,
                        'order_item_id' => $item->id,
                        'product_id' => $product->id,
                    ]);

                    continue;
                }

                $requestedQuantity = max((int) $item->quantity, 0);

                if ($requestedQuantity < 1) {
                    $this->failStockDeduction($lockedOrder, "Invalid quantity for {$product->name}.", [
                        'order_item_id' => $item->id,
                        'product_id' => $product->id,
                        'requested_quantity' => $item->quantity,
                    ]);
                }

                if (! $product->isAvailableForPurchase()) {
                    $this->failStockDeduction($lockedOrder, "{$product->name} is out of stock.", [
                        'order_item_id' => $item->id,
                        'product_id' => $product->id,
                        'stock_status' => $product->stock_status,
                        'stock_quantity' => $product->stock_quantity,
                    ]);
                }

                $previousStock = (int) $product->stock_quantity;

                if ($previousStock < $requestedQuantity) {
                    $this->failStockDeduction($lockedOrder, "Insufficient stock for {$product->name}.", [
                        'order_item_id' => $item->id,
                        'product_id' => $product->id,
                        'available_stock_quantity' => $previousStock,
                        'requested_quantity' => $requestedQuantity,
                    ]);
                }

                $newStock = $previousStock - $requestedQuantity;

                $product->forceFill([
                    'stock_quantity' => $newStock,
                ])->save();

                Log::info('Product stock deducted for paid order.', [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'order_item_id' => $item->id,
                    'product_id' => $product->id,
                    'previous_stock_quantity' => $previousStock,
                    'deducted_quantity' => $requestedQuantity,
                    'new_stock_quantity' => $newStock,
                    'new_stock_status' => $product->stock_status,
                ]);
            }

            $lockedOrder->forceFill([
                'stock_deducted_at' => now(),
            ])->saveQuietly();

            Log::info('Completed stock deduction for paid order.', [
                'order_id' => $lockedOrder->id,
                'order_number' => $lockedOrder->order_number,
                'stock_deducted_at' => $lockedOrder->stock_deducted_at,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws ValidationException
     */
    private function failStockDeduction(Order $order, string $message, array $context = []): never
    {
        Log::error('Unable to deduct stock for paid order.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'message' => $message,
        ] + $context);

        throw ValidationException::withMessages([
            'stock' => $message,
        ]);
    }
}
