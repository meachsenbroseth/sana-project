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
                    <li><a wire:navigate href="{{ route('customer.dashboard') }}" class="text-gray-500 hover:text-gray-900">Account</a></li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-900 font-medium">Orders</li>
                </ol>
            </nav>

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
                <h1 class="text-3xl font-bold text-gray-900">Orders</h1>

                <select wire:model.live="statusFilter" class="border-gray-300 rounded-md text-sm focus:ring-black focus:border-black py-2 pl-3 pr-10 shadow-sm">
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
            <div class="bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.04)] border border-gray-100 overflow-hidden">
                <div class="overflow-hidden">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-[#fcfcfc]">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold tracking-wider text-gray-400 uppercase">Order No</th>
                                <th scope="col" class="hidden sm:table-cell px-4 py-3 text-left text-[11px] font-bold tracking-wider text-gray-400 uppercase">Date</th>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold tracking-wider text-gray-400 uppercase">Status</th>
                                <th scope="col" class="hidden md:table-cell px-4 py-3 text-left text-[11px] font-bold tracking-wider text-gray-400 uppercase">Items</th>
                                <th scope="col" class="hidden lg:table-cell px-4 py-3 text-left text-[11px] font-bold tracking-wider text-gray-400 uppercase">Payment</th>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold tracking-wider text-gray-400 uppercase">Total</th>
                                <th scope="col" class="px-4 py-3 text-right text-[11px] font-bold tracking-wider text-gray-400 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach ($orders as $order)
                            <tr x-on:click="$refs.link.click()" class="cursor-pointer hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-bold text-gray-900">
                                    {{ $order->order_number }}
                                </td>
                                <td class="hidden sm:table-cell px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                    {{ $order->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2.5 py-1 inline-flex rounded text-xs font-semibold text-white {{ $order->status === 'delivered' ? 'bg-[#1e874b]' : ($order->status === 'cancelled' ? 'bg-red-600' : ($order->status === 'shipped' ? 'bg-blue-600' : ($order->status === 'processing' ? 'bg-indigo-600' : 'bg-gray-600'))) }}">
                                        {{ $order->status === 'delivered' ? 'Completed' : ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="hidden md:table-cell px-4 py-3 text-sm text-gray-900">
                                    {{ $order->items->sum('quantity') }}
                                </td>
                                <td class="hidden lg:table-cell px-4 py-3 text-sm text-gray-900">
                                    @if ($order->payment_method === 'KHQR')
                                        ABA KHQR
                                    @elseif($order->payment_method === 'cash_on_delivery')
                                        Cash on Delivery
                                    @else
                                        {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                                    ${{ number_format($order->total, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium">
                                    <a 
                                        x-ref="link" 
                                        x-on:click.stop 
                                        wire:navigate 
                                        href="{{ route('customer.orders.show', $order->id) }}" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md shadow-sm hover:bg-blue-700 transition-colors"
                                    >
                                        <flux:icon.eye class="w-3.5 h-3.5" />
                                        <span class="hidden sm:inline">View Details</span>
                                        <span class="sm:hidden">View</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
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
                    <button wire:click="$set('statusFilter', '')" class="bg-black text-white px-6 py-2.5 rounded-full text-sm font-medium hover:bg-gray-800 transition">
                        Show All Orders
                    </button>
                @else
                    <a wire:navigate href="{{ route('products.index') }}" class="inline-block bg-black text-white px-6 py-2.5 rounded-full text-sm font-medium hover:bg-gray-800 transition">
                        Start Shopping
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>