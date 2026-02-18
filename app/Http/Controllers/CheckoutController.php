<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function success(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            // Ensure the order belongs to the logged-in customer
            ->where('customer_id', auth('customer')->id())
            ->with(['items.product.primeImage', 'customer'])
            ->firstOrFail(); // Fixed typo: firstOrFial -> firstOrFail

        // Return the success view
        return view('checkout.success', compact('order'));
    }

    public function cancel(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail(); // Fixed typo: firstOrFial -> firstOrFail

        // Return the cancel view
        return view('checkout.cancel', compact('order'));
    }
}
