<?php

    use Livewire\Component;
    use App\Models\Order;
    use App\Models\Review;
    use App\Models\OrderItem;
    use Barryvdh\DomPDF\Facade\Pdf;
    use Symfony\Component\HttpFoundation\StreamedResponse;

    new class extends Component
    {
        public Order $order;

        public array $reviewedProductIds = [];

        public bool $showReviewModal = false;

        public ?int $reviewingItemId = null;

        public int $rating = 5;

        public string $comment = '';

        public function mount($id): void
        {
            $this->order = Order::where('id', $id)
                ->where('customer_id', auth('customer')->id())
                ->with(['items.product.primeImage', 'statusHistories'])
                ->firstOrFail();

            $this->reviewedProductIds = Review::query()
                ->where('customer_id', auth('customer')->id())
                ->where('order_id', $this->order->id)
                ->pluck('product_id')
                ->all();
        }

        public function downloadInvoice(): StreamedResponse
        {
            $pdf = Pdf::loadView('pdf.invoice', ['order' => $this->order]);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'Invoice-' . $this->order->order_number . '.pdf');
        }

        public function openReviewModal(int $itemId): void
        {
            $item = $this->order->items->firstWhere('id', $itemId);

            if (! $item instanceof OrderItem) {
                return;
            }

            if ($this->order->status !== 'delivered' || ! $item->product) {
                return;
            }

            if (in_array((int) $item->product_id, $this->reviewedProductIds, true)) {
                return;
            }

            $this->reviewingItemId = $item->id;
            $this->rating = 5;
            $this->comment = '';
            $this->resetErrorBag();
            $this->showReviewModal = true;
        }

        public function closeReviewModal(): void
        {
            $this->showReviewModal = false;
            $this->reviewingItemId = null;
            $this->rating = 5;
            $this->comment = '';
            $this->resetErrorBag();
        }

        public function submitReview(): void
        {
            $this->validate([
                'rating' => ['required', 'integer', 'between:1,5'],
                'comment' => ['required', 'string', 'max:5000'],
            ]);

            $item = $this->reviewingItem;

            if (! $item instanceof OrderItem || ! $item->product) {
                $this->addError('comment', 'Invalid product review request.');

                return;
            }

            if ($this->order->status !== 'delivered') {
                $this->addError('comment', 'You can only review delivered orders.');

                return;
            }

            $alreadyReviewed = Review::query()
                ->where('customer_id', auth('customer')->id())
                ->where('order_id', $this->order->id)
                ->where('product_id', $item->product_id)
                ->exists();

            if ($alreadyReviewed) {
                $this->addError('comment', 'You have already reviewed this product for this order.');

                return;
            }

            Review::query()->create([
                'customer_id' => auth('customer')->id(),
                'product_id' => $item->product_id,
                'order_id' => $this->order->id,
                'rating' => $this->rating,
                'comment' => $this->comment,
                'is_verified_purchase' => true,
                'is_approved' => false,
            ]);

            $this->reviewedProductIds[] = (int) $item->product_id;
            $this->closeReviewModal();
            session()->flash('review_success', 'Review submitted and awaiting approval.');
        }

        public function getReviewingItemProperty(): ?OrderItem
        {
            if (! $this->reviewingItemId) {
                return null;
            }

            $item = $this->order->items->firstWhere('id', $this->reviewingItemId);

            return $item instanceof OrderItem ? $item : null;
        }

        /**
         * Determine the visual state of a given status step.
         *
         * @return array{label: string, completed: bool, current: bool, upcoming: bool, icon: string, color: string, bg: string, ring: string}
         */
        public function getStepState(string $step): array
        {
            $statusOrder = ['pending', 'processing', 'shipped', 'delivered'];
            $currentStatus = $this->order->status;

            if ($currentStatus === 'cancelled') {
                return [
                    'label' => match ($step) {
                        'pending' => 'Order Placed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        default => ucfirst($step),
                    },
                    'completed' => $step === 'pending',
                    'current' => false,
                    'upcoming' => $step !== 'pending',
                    'icon' => match ($step) {
                        'pending' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                        'processing' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
                        'shipped' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 13.5V5.25A2.25 2.25 0 0 0 14.25 3h-4.5A2.25 2.25 0 0 0 7.5 5.25v8.25',
                        'delivered' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
                        default => '',
                    },
                    'color' => $step === 'pending' ? 'text-red-600' : 'text-gray-400',
                    'bg' => $step === 'pending' ? 'bg-red-100' : 'bg-gray-100',
                    'ring' => $step === 'pending' ? 'ring-red-600' : 'ring-gray-300',
                ];
            }

            $currentIndex = array_search($currentStatus, $statusOrder, true);
            $stepIndex = array_search($step, $statusOrder, true);

            $completed = $currentIndex !== false && $stepIndex !== false && $stepIndex < $currentIndex;
            $current = $step === $currentStatus;
            $upcoming = $currentIndex !== false && $stepIndex !== false && $stepIndex > $currentIndex;

            return [
                'label' => match ($step) {
                    'pending' => 'Order Placed',
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    default => ucfirst($step),
                },
                'completed' => $completed,
                'current' => $current,
                'upcoming' => $upcoming,
                'icon' => match ($step) {
                    'pending' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    'processing' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
                    'shipped' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 13.5V5.25A2.25 2.25 0 0 0 14.25 3h-4.5A2.25 2.25 0 0 0 7.5 5.25v8.25',
                    'delivered' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
                    default => '',
                },
                'color' => match (true) {
                    $completed => 'text-[#1e874b]',
                    $current => 'text-blue-600',
                    default => 'text-gray-400',
                },
                'bg' => match (true) {
                    $completed => 'bg-[#1e874b]',
                    $current => 'bg-blue-600',
                    default => 'bg-gray-200',
                },
                'ring' => match (true) {
                    $completed => 'ring-[#1e874b]',
                    $current => 'ring-blue-600',
                    default => 'ring-gray-200',
                },
            ];
        }

        public function getTimelineDate(string $step): ?string
        {
            if ($this->order->status === 'cancelled') {
                return $step === 'pending'
                    ? ($this->order->statusHistories->where('status', 'pending')->first()?->created_at?->format('d M Y H:i') ?? $this->order->created_at->format('d M Y H:i'))
                    : null;
            }

            $history = $this->order->statusHistories->where('status', $step)->first();

            if ($history) {
                return $history->created_at->format('d M Y H:i');
            }

            if ($step === 'pending') {
                return $this->order->created_at->format('d M Y H:i');
            }

            return null;
        }
    };
?>

<div class="min-h-screen py-10">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

        <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <nav class="text-sm mb-4">
                    <ol class="flex items-center gap-2">
                        <li><a wire:navigate href="{{ route('customer.dashboard') }}" class="text-gray-500 hover:text-gray-900">Account</a></li>
                        <li class="text-gray-400">/</li>
                        <li><a wire:navigate href="{{ route('customer.orders') }}" class="text-gray-500 hover:text-gray-900">Orders</a></li>
                        <li class="text-gray-400">/</li>
                        <li class="text-gray-900 font-medium">#{{ $order->order_number }}</li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">Order information</h1>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($order->status === 'shipped')
                    <form method="POST" action="{{ route('customer.orders.confirm-delivery', $order) }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#1e874b] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-[#176a3b] focus:ring-4 focus:ring-green-100 sm:w-auto">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                            </svg>
                            Confirm Delivery
                        </button>
                    </form>
                @endif

                <button wire:click="downloadInvoice" type="button" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-blue-800 focus:ring-4 focus:ring-gray-200 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download Invoice
                </button>
            </div>
        </div>

        @if (session()->has('delivery_confirmed'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('delivery_confirmed') }}
            </div>
        @endif

        @if (session()->has('review_success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('review_success') }}
            </div>
        @endif

        @error('order')
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        <div class="bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.04)] border border-gray-100 p-8">

            {{-- Two column layout: Order Info | Shipping Address --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-10">

                {{-- Order Information --}}
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
                            <span class="text-gray-500">Payment method:</span>
                            <span class="font-medium text-gray-900">
                                @if($order->payment_method === 'KHQR')
                                    Bakong KHQR
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

                {{-- Shipping Address --}}
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

                                @if ($order->status === 'delivered' && $item->product && !in_array((int) $item->product_id, $reviewedProductIds, true))
                                    <button
                                        wire:click="openReviewModal({{ $item->id }})"
                                        type="button"
                                        class="mt-3 w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition"
                                    >
                                        Write Review
                                    </button>
                                @endif
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

            {{-- Shipping Information with Stepper --}}
            <div class="grid grid-cols-1 gap-10 ">
                <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4">Shipping Information</h3>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    {{-- Left: Shipping Details --}}
                    <div class="space-y-4">
                        @if ($order->status === 'cancelled')
                            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                                <p class="text-sm font-semibold text-red-700">This order has been cancelled.</p>
                            </div>
                        @endif
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

                    {{-- Right: Shipping Status Stepper --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-4">Shipping Progress</h4>
                        <div class="relative pl-2">
                            @php
                                $steps = ['pending', 'processing', 'shipped', 'delivered'];
                            @endphp

                            @foreach ($steps as $index => $step)
                                @php    
                                    $state = $this->getStepState($step);
                                    $date = $this->getTimelineDate($step);
                                    $isLast = $index === count($steps) - 1;
                                @endphp

                                <div class="relative flex gap-4 {{ $isLast ? '' : 'pb-6' }}">
                                    {{-- Connector line --}}
                                    @if (! $isLast)
                                        <div class="absolute left-[15px] top-8 w-0.5 h-full {{ $state['completed'] ? 'bg-[#1e874b]' : 'bg-gray-200' }}"></div>
                                    @endif

                                    {{-- Icon / Dot --}}
                                    <div class="relative z-10 flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full ring-2 {{ $state['bg'] }} {{ $state['ring'] }} {{ $state['completed'] || $state['current'] ? '' : 'bg-white' }}">
                                            @if ($state['completed'])
                                                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            @elseif ($state['current'])
                                                <div class="h-2.5 w-2.5 rounded-full bg-white"></div>
                                            @else
                                                <div class="h-2 w-2 rounded-full bg-gray-300"></div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Content --}}
                                    <div class="flex-1 pt-0.5">
                                        <p class="text-sm font-semibold {{ $state['color'] }}">
                                            {{ $state['label'] }}
                                        </p>
                                        @if ($date)
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $date }}</p>
                                        @else
                                            <p class="text-xs text-gray-400 mt-0.5">Pending</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @if ($showReviewModal && $this->reviewingItem && $this->reviewingItem->product)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="closeReviewModal"></div>
            <div class="relative z-10 w-full max-w-xl rounded-xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Write Review</h3>
                    <button wire:click="closeReviewModal" type="button" class="text-gray-400 hover:text-gray-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="submitReview" class="space-y-5 px-6 py-5">
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 overflow-hidden rounded-md border border-gray-100 bg-gray-50">
                            @if ($this->reviewingItem->product->primeImage)
                                <img
                                    src="{{ asset('storage/' . $this->reviewingItem->product->primeImage->image_path) }}"
                                    alt="{{ $this->reviewingItem->product_name }}"
                                    class="h-full w-full object-cover"
                                >
                            @endif
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Product</p>
                            <p class="font-semibold text-gray-900">{{ $this->reviewingItem->product_name }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Rating</label>
                        <div class="flex items-center gap-2">
                            @for ($star = 1; $star <= 5; $star++)
                                <button
                                    type="button"
                                    wire:click="$set('rating', {{ $star }})"
                                    class="text-2xl leading-none {{ $rating >= $star ? 'text-yellow-400' : 'text-gray-300' }}"
                                >
                                    &#9733;
                                </button>
                            @endfor
                        </div>
                        @error('rating')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="review-comment" class="mb-2 block text-sm font-medium text-gray-700">Review</label>
                        <textarea
                            id="review-comment"
                            wire:model="comment"
                            rows="4"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            placeholder="Share your experience with this product"
                        ></textarea>
                        @error('comment')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" wire:click="closeReviewModal" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" wire:loading.attr="disabled" wire:target="submitReview">
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>