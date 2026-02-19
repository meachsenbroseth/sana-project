<?php

use Livewire\Component;
use App\Models\Order;

new class extends Component
{
    public Order $order;

    public function mount($id)
    {
        $this->order = Order::where('id', $id)
            ->where('customer_id', auth('customer')->id())
            ->with(['items.product.primeImage', 'statusHistories'])
            ->firstOrFail();
    }
};
?>

<div class="bg-[#faf9f6] min-h-screen py-10">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <nav class="text-sm mb-4">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('customer.dashboard') }}" class="text-gray-500 hover:text-gray-900">Account</a></li>
                    <li class="text-gray-400">/</li>
                    <li><a href="{{ route('customer.orders') }}" class="text-gray-500 hover:text-gray-900">Orders</a></li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-900 font-medium">#{{ $order->order_number }}</li>
                </ol>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900">Order information</h1>
        </div>

        <div class="bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.04)] border border-gray-100 p-8">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-10">

                <div>
                    <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4">Order information</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Order number:</span>
                            <span class="font-bold text-gray-900">#{{ $order->order_number }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Time:</span>
                            <span class="font-medium text-gray-900">{{ $order->created_at->format('d M Y H:i:s') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Order status:</span>
                            <span class="px-2.5 py-0.5 rounded text-xs font-semibold text-white {{
                                $order->status === 'delivered' ? 'bg-[#1e874b]' :
                                ($order->status === 'cancelled' ? 'bg-red-600' :
                                ($order->status === 'shipped' ? 'bg-blue-600' :
                                ($order->status === 'processing' ? 'bg-indigo-600' : 'bg-gray-600')))
                            }}">
                                {{ $order->status === 'delivered' ? 'Completed' : ucfirst($order->status) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Payment method:</span>
                            <span class="font-medium text-gray-900">
                                @if($order->payment_method === 'KHQR')
                                    ABA KHQR
                                @elseif($order->payment_method === 'cash_on_delivery')
                                    Cash on Delivery
                                @else
                                    {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Payment status:</span>
                            <span class="px-2.5 py-0.5 rounded text-xs font-semibold text-white {{
                                $order->payment_status === 'paid' ? 'bg-[#1e874b]' : 'bg-yellow-500'
                            }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4">Shipping Address</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Full Name:</span>
                            <span class="font-medium text-gray-900">{{ $order->shipping_full_name }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Phone:</span>
                            <span class="font-medium text-gray-900">{{ $order->shipping_phone }}</span>
                        </div>
                        <div class="flex justify-between items-start text-sm">
                            <span class="text-gray-500 mr-4 mt-0.5">Address:</span>
                            <span class="font-medium text-gray-900 text-right leading-relaxed">
                                {{ $order->shipping_address_line_1 }}<br>
                                @if($order->shipping_address_line_2)
                                    {{ $order->shipping_address_line_2 }}<br>
                                @endif
                                {{ $order->shipping_city }}@if($order->shipping_state), {{ $order->shipping_state }}@endif<br>
                                {{ $order->shipping_country }}, {{ $order->shipping_postal_code }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-10">
                <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4">Products</h3>

                <div class="space-y-6">
                    @foreach($order->items as $item)
                        <div class="flex flex-col sm:flex-row justify-between items-start">
                            <div class="flex gap-4">
                                <div class="w-16 h-16 rounded bg-gray-50 border border-gray-100 flex-shrink-0 overflow-hidden">
                                    @if($item->product && $item->product->primeImage)
                                        <img src="{{ asset('storage/' . $item->product->primeImage->image_path) }}"
                                             alt="{{ $item->product_name }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-gray-900">{{ $item->product_name }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">{{ $item->product_sku }}</p>
                                    {{-- Use unit_amount if price throws an error --}}
                                    <p class="text-xs text-gray-400 mt-2">Price: ${{ number_format($item->unit_amount ?? $item->price, 2) }}</p>
                                </div>
                            </div>

                            <div class="w-full sm:w-48 mt-4 sm:mt-0 text-sm">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-gray-500">Price:</span>
                                    <span class="font-medium text-gray-900">${{ number_format($item->unit_amount ?? $item->price, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-gray-500">Quantity:</span>
                                    <span class="font-medium text-gray-900">{{ $item->quantity }}</span>
                                </div>
                                <div class="flex justify-between items-center font-bold mt-2 pt-1">
                                    <span class="text-gray-900">Total:</span>
                                    <span class="text-gray-900">${{ number_format($item->total_amount ?? $item->subtotal, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <div class="flex flex-col items-end space-y-2">
                        @if($order->discount_amount > 0)
                        <div class="flex justify-between w-full sm:w-64 text-sm">
                            <span class="text-gray-500">Discount:</span>
                            <span class="text-[#1e874b] font-medium">-${{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between w-full sm:w-64 text-sm">
                            <span class="text-gray-500">Shipping fee:</span>
                            <span class="font-medium text-gray-900">
                                {{ $order->shipping_cost > 0 ? '$' . number_format($order->shipping_cost, 2) : 'Free' }}
                            </span>
                        </div>
                        @if($order->tax_amount > 0)
                            <div class="flex justify-between w-full sm:w-64 text-sm">
                                <span class="text-gray-500">Tax:</span>
                                <span class="font-medium text-gray-900">${{ number_format($order->tax_amount, 2) }}</span>
                            </div>
                        @endif

                        <div class="w-full border-t border-dashed border-gray-200 my-2"></div>

                        <div class="flex justify-between w-full sm:w-64">
                            <span class="text-base font-bold text-gray-900">Total Amount:</span>
                            <span class="text-base font-bold text-gray-900">${{ number_format($order->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4">Shipping Information</h3>
                <div class="space-y-4 max-w-2xl">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Shipping Status:</span>
                        <span class="px-2.5 py-0.5 rounded text-xs font-semibold text-white {{
                            $order->status === 'delivered' ? 'bg-[#1e874b]' :
                            ($order->status === 'shipped' ? 'bg-blue-600' : 'bg-gray-400')
                        }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Shipping Company Name:</span>
                        <span class="font-medium text-gray-900">{{ $order->shipping_method ?? 'Standard Express' }}</span>
                    </div>
                    @if($order->tracking_number)
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Tracking ID:</span>
                            <span class="font-medium text-gray-900">{{ $order->tracking_number }}</span>
                        </div>
                    @endif
                    @if($order->status === 'shipped' || $order->status === 'delivered')
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Date Shipped:</span>
                            <span class="font-medium text-gray-900">
                                {{ $order->statusHistories->where('status', 'shipped')->first()?->created_at->format('Y-m-d H:i:s') ?? 'N/A' }}
                            </span>
                        </div>
                    @endif
                    @if($order->customer_notes)
                        <div class="flex justify-between items-start text-sm mt-4 pt-4 border-t border-gray-50">
                            <span class="text-gray-500 mr-4">Order Notes:</span>
                            <span class="font-medium text-gray-900 text-right">{{ $order->customer_notes }}</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
