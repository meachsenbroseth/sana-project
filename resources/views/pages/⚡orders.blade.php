<?php

use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $statusFilter = '';

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function with()
    {
        $query = auth('customer')
            ->user()
            ->orders()
            ->with(['items'])
            ->latest();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return [
            'orders' => $query->paginate(10),
        ];
    }
};
?>

<div class="min-h-screen py-10">

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

        <div class="mb-10 border-b border-gray-200 pb-6">
            <nav class="text-sm mb-4">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('customer.dashboard') }}" class="text-gray-500 hover:text-gray-900">Account</a>
                    </li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-900 font-medium">Orders</li>
                </ol>
            </nav>

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
                <h1 class="text-3xl font-bold text-gray-900">Orders</h1>

                <select wire:model.live="statusFilter"
                    class="border-gray-300 rounded-md text-sm focus:ring-black focus:border-black py-2 pl-3 pr-10 shadow-sm">
                    <option value="">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        @if ($orders->count() > 0)
            <div class="space-y-6">
                @foreach ($orders as $order)
                    <div
                        class="bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.04)] border border-gray-100 overflow-hidden">

                        <div class="p-6 md:p-8">
                            <h2 class="text-lg font-bold text-gray-900 mb-2">{{ $order->order_number }}</h2>
                            <div class="flex items-center gap-3 text-sm mb-6">
                                <span
                                    class="px-2.5 py-1 rounded text-xs font-semibold text-white
                                    {{ $order->status === 'delivered'
                                        ? 'bg-[#1e874b]'
                                        : ($order->status === 'cancelled'
                                            ? 'bg-red-600'
                                            : ($order->status === 'shipped'
                                                ? 'bg-blue-600'
                                                : ($order->status === 'processing'
                                                    ? 'bg-indigo-600'
                                                    : 'bg-gray-600'))) }}">
                                    {{ $order->status === 'delivered' ? 'Completed' : ucfirst($order->status) }}
                                </span>
                                <span class="text-gray-400">•</span>
                                <span class="text-gray-500">{{ $order->created_at->format('M d, Y') }}</span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 border-t border-gray-100 pt-6">
                                <div>
                                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase mb-1">Total
                                        Amount</p>
                                    <p class="text-base font-medium text-gray-900">
                                        ${{ number_format($order->total, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase mb-1">Items
                                    </p>
                                    <p class="text-base font-medium text-gray-900">{{ $order->items->sum('quantity') }}
                                    </p>
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <p class="text-[11px] font-bold tracking-wider text-gray-400 uppercase mb-1">Payment
                                    </p>
                                    <p class="text-base font-medium text-gray-900">
                                        @if ($order->payment_method === 'KHQR')
                                            ABA KHQR
                                        @elseif($order->payment_method === 'cash_on_delivery')
                                            Cash on Delivery
                                        @else
                                            {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-100 flex justify-end bg-[#fcfcfc]">
                            <a href="{{ route('customer.orders.show', $order->id) }}"
                                class="inline-flex items-center justify-center gap-2 bg-[#0a0a0a] text-white px-6 py-2.5 rounded-full text-sm font-medium hover:bg-gray-800 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View Details
                            </a>
                        </div>

                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No orders found</h3>
                <p class="text-gray-500 mb-6">
                    @if ($statusFilter)
                        No orders with status "{{ $statusFilter }}"
                    @else
                        You haven't placed any orders yet.
                    @endif
                </p>
                @if ($statusFilter)
                    <button wire:click="$set('statusFilter', '')"
                        class="bg-black text-white px-6 py-2.5 rounded-full text-sm font-medium hover:bg-gray-800 transition">
                        Show All Orders
                    </button>
                @else
                    <a href="{{ route('products.index') }}"
                        class="inline-block bg-black text-white px-6 py-2.5 rounded-full text-sm font-medium hover:bg-gray-800 transition">
                        Start Shopping
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
