<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmOrderDeliveryRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class CustomerOrderDeliveryConfirmationController extends Controller
{
    public function __invoke(ConfirmOrderDeliveryRequest $request, Order $order): RedirectResponse
    {
        if ($order->status !== 'shipped') {
            throw ValidationException::withMessages([
                'order' => $this->statusErrorMessage($order),
            ]);
        }

        $order->forceFill([
            'status' => 'delivered',
        ])->saveQuietly();

        $order->statusHistories()->create([
            'status' => 'delivered',
            'notes' => 'Delivery confirmed by customer.',
        ]);

        return back()->with('delivery_confirmed', 'Delivery confirmed successfully.');
    }

    private function statusErrorMessage(Order $order): string
    {
        return match ($order->status) {
            'delivered' => 'This order has already been marked as delivered.',
            'cancelled' => 'Cancelled orders cannot be marked as delivered.',
            default => 'Only shipped orders can be marked as delivered.',
        };
    }
}
