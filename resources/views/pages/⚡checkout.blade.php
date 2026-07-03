<?php
    use Livewire\Component;
    use Livewire\Attributes\Computed;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Validation\ValidationException;

    use App\Models\Address;
    use App\Models\Order;
    use App\Models\OrderItem;
    use App\Models\Product;
    use App\Models\ShippingMethod;
    use App\Services\OrderStockService;

    use KHQR\BakongKHQR;
    use KHQR\Helpers\KHQRData;
    use KHQR\Models\IndividualInfo;

    use BaconQrCode\Renderer\ImageRenderer;
    use BaconQrCode\Renderer\Image\SvgImageBackEnd;
    use BaconQrCode\Renderer\RendererStyle\RendererStyle;
    use BaconQrCode\Writer;

    new class extends Component
    {
        // -------------------------------------------------------------------------
        // Constants
        // -------------------------------------------------------------------------

        const PAYMENT_TIMEOUT_SECONDS = 120;
        const QR_SIZE_PX              = 250;
        const STEP_SHIPPING           = 1;
        const STEP_REVIEW             = 2;
        const STEP_PAYMENT            = 3;

        // -------------------------------------------------------------------------
        // Step state
        // -------------------------------------------------------------------------

        public int $step = self::STEP_SHIPPING;

        // -------------------------------------------------------------------------
        // Address fields
        // -------------------------------------------------------------------------

        public bool   $useExistingAddress = true;
        public bool   $saveAddress        = false;
        public ?int   $selectedAddressId  = null;
        public string $full_name          = '';
        public string $phone              = '';
        public string $address_line_1     = '';
        public string $address_line_2     = '';
        public string $city               = '';
        public string $state              = '';
        public string $country            = 'KH';

        // -------------------------------------------------------------------------
        // Order / payment fields
        // -------------------------------------------------------------------------

        public array  $cart                    = [];
        public string $paymentMethod           = 'KHQR';
        public string $customerNotes           = '';
        public ?int   $selectedShippingMethodId = null;
        public string $merchantName            = '';

        // -------------------------------------------------------------------------
        // KHQR modal state
        // -------------------------------------------------------------------------

        public bool    $showKhqrModal     = false;
        public bool    $orderProcessing   = false;
        public string  $khqrString        = '';   // rendered SVG
        public ?string $khqrStringRaw     = null; // raw QR string
        public ?string $khqrMd5           = null;
        public ?int    $paymentStartedAtTs = null;
        public int     $paymentTimeout    = self::PAYMENT_TIMEOUT_SECONDS;
        public int     $timeLeft          = self::PAYMENT_TIMEOUT_SECONDS;

        // -------------------------------------------------------------------------
        // Lifecycle
        // -------------------------------------------------------------------------

        public function mount(): void
        {
            $this->cart = session()->get('cart', []);

            if (empty($this->cart)) {
                redirect()->route('cart.index');
                return;
            }

            if (! $this->validateCartAvailability()) {
                redirect()->route('cart.index');
                return;
            }

            $this->merchantName = env('BAKONG_MERCHANT_NAME');

            $this->bootAddressDefaults();

            $this->selectedShippingMethodId = $this->shippingMethods->first()?->id;
        }

        private function bootAddressDefaults(): void
        {
            $customer = auth('customer')->user();

            $this->full_name = $customer->name;
            $this->phone     = $customer->phone ?? '';

            $defaultAddress = $customer->address()->where('is_default', true)->first()
                ?? $customer->address()->first();

            if ($defaultAddress) {
                $this->selectedAddressId = $defaultAddress->id;
            } else {
                $this->useExistingAddress = false;
            }
        }

        // -------------------------------------------------------------------------
        // Computed properties
        // -------------------------------------------------------------------------

        #[Computed]
        public function addresses()
        {
            return auth('customer')->user()->address ?? collect();
        }

        #[Computed]
        public function shippingMethods()
        {
            return ShippingMethod::query()->active()->orderBy('name')->get();
        }

        #[Computed]
        public function subtotal(): float
        {
            return $this->getSubtotal();
        }

        #[Computed]
        public function shippingCost(): float
        {
            return $this->getShippingCost();
        }

        #[Computed]
        public function total(): float
        {
            return $this->subtotal + $this->shippingCost;
        }

        // -------------------------------------------------------------------------
        // Step navigation
        // -------------------------------------------------------------------------

        public function nextStep(): void
        {
            if ($this->step === self::STEP_SHIPPING) {
                if ($this->validateAddress() && $this->validateShippingMethod()) {
                    $this->step = self::STEP_REVIEW;
                }
                return;
            }

            if ($this->step === self::STEP_REVIEW) {
                if (! $this->validateCartAvailability()) {
                    return;
                }

                $this->step = self::STEP_PAYMENT;
            }
        }

        public function previousStep(): void
        {
            if ($this->step > self::STEP_SHIPPING) {
                $this->step--;
            }
        }

        // -------------------------------------------------------------------------
        // Validation
        // -------------------------------------------------------------------------

        protected function validateAddress(): bool
        {
            if (! $this->useExistingAddress) {
                $this->validate([
                    'full_name'      => 'required|string|max:255',
                    'phone'          => 'required|string|max:20',
                    'address_line_1' => 'required|string|max:255',
                    'city'           => 'required|string|max:100',
                    'country'        => 'required|string|max:2',
                ]);

                return true;
            }

            if (! $this->selectedAddressId) {
                $hasAny = auth('customer')->user()->address()->exists();

                if (! $hasAny) {
                    $this->useExistingAddress = false;
                    session()->flash('error', 'No saved addresses found. Please add a new address.');
                } else {
                    session()->flash('error', 'Please select a shipping address.');
                }

                return false;
            }

            $valid = Address::where('id', $this->selectedAddressId)
                ->where('customer_id', auth('customer')->id())
                ->exists();

            if (! $valid) {
                session()->flash('error', 'Invalid address selected.');
                return false;
            }

            return true;
        }

        protected function validateShippingMethod(): bool
        {
            if ($this->shippingMethods->isEmpty()) {
                session()->flash('error', 'No active shipping methods are available right now.');
                return false;
            }

            $exists = ShippingMethod::query()
                ->active()
                ->whereKey($this->selectedShippingMethodId)
                ->exists();

            if (! $exists) {
                session()->flash('error', 'Please select an active shipping method.');
                return false;
            }

            return true;
        }

        // -------------------------------------------------------------------------
        // Order placement
        // -------------------------------------------------------------------------

        public function placeOrder(): mixed
        {
            if (! $this->validateCartAvailability()) {
                $this->step = self::STEP_REVIEW;
                return null;
            }

            if (! $this->validateAddress() || ! $this->validateShippingMethod()) {
                $this->step = self::STEP_SHIPPING;
                return null;
            }

            if ($this->paymentMethod === 'KHQR') {
                $this->processKhqrPayment();
                return null;
            }

            // Cash on Delivery
            try {
                $order = $this->finalizeOrderInDatabase('pending', 'pending');
                return redirect()->route('checkout.success', $order->id)
                    ->with('success', 'Order placed successfully!');
            } catch (\Exception $e) {
                session()->flash('error', 'Error placing order: ' . $e->getMessage());
                return null;
            }
        }

        // -------------------------------------------------------------------------
        // KHQR payment flow
        // -------------------------------------------------------------------------

        protected function processKhqrPayment(): void
        {
            try {
                $merchant = new IndividualInfo(
                    bakongAccountID:      env('BAKONG_MERCHANT_ID'),
                    merchantName:         env('BAKONG_MERCHANT_NAME'),
                    merchantCity:         env('BAKONG_MERCHANT_CITY'),
                    currency:             KHQRData::CURRENCY_KHR,
                    amount:               $this->total,
                    expirationTimestamp:  (time() + self::PAYMENT_TIMEOUT_SECONDS) * 1000,
                );

                $qrResponse = BakongKHQR::generateIndividual($merchant);

                if (! isset($qrResponse->data['qr'], $qrResponse->data['md5'])) {
                    session()->flash('error', 'Failed to generate KHQR code.');
                    return;
                }

                $this->khqrStringRaw      = $qrResponse->data['qr'];
                $this->khqrMd5            = $qrResponse->data['md5'];
                $this->orderProcessing    = false;
                $this->paymentStartedAtTs = now()->timestamp;
                $this->timeLeft           = $this->paymentTimeout;
                $this->khqrString         = $this->renderQrSvg($this->khqrStringRaw);
                $this->showKhqrModal      = true;

            } catch (\Exception $e) {
                session()->flash('error', 'Error generating payment: ' . $e->getMessage());
            }
        }

        private function renderQrSvg(string $raw): string
        {
            $renderer = new ImageRenderer(
                new RendererStyle(self::QR_SIZE_PX),
                new SvgImageBackEnd(),
            );

            $fullSvg = (new Writer($renderer))->writeString($raw);

            return trim(substr($fullSvg, strpos($fullSvg, '<svg')));
        }

        public function checkKhqrStatus(): mixed
        {
            if (! $this->khqrMd5 || ! $this->paymentStartedAtTs || $this->orderProcessing) {
                return null;
            }

            $this->timeLeft = max(0, $this->paymentTimeout - (now()->timestamp - $this->paymentStartedAtTs));

            if ($this->timeLeft <= 0) {
                $this->cancelPayment('Payment timed out.');
                return null;
            }

            try {
                $this->verifyKhqrTransaction();
            } catch (\Throwable $e) {
                session()->flash('error', 'KHQR verify error: ' . $e->getMessage());
            }

            return null;
        }

        private function verifyKhqrTransaction(): mixed
        {
            $token = env('BAKONG_TOKEN');

            if (! $token) {
                session()->flash('error', 'BAKONG_TOKEN is missing.');
                return null;
            }

            $result = (new BakongKHQR($token))->checkTransactionByMD5($this->khqrMd5);

            $responseCode = data_get($result, 'responseCode')
                ?? data_get($result, 'data.responseCode')
                ?? data_get($result, 'response.responseCode');

            if ((int) $responseCode !== 0) {
                return null;
            }

            return $this->handleSuccessfulPayment();
        }

        private function handleSuccessfulPayment(): mixed
        {
            $this->orderProcessing = true;

            // Guard against double-processing
            $existing = Order::where('transaction_id', $this->khqrMd5)->first();

            if ($existing) {
                $this->showKhqrModal = false;
                return redirect()->route('checkout.success', $existing->id);
            }

            $order = $this->finalizeOrderInDatabase('paid', 'processing', $this->khqrMd5);

            $this->showKhqrModal = false;

            return redirect()->route('checkout.success', $order->id);
        }

        public function cancelPayment(string $reason = 'Payment cancelled by user.'): void
        {
            $this->showKhqrModal = false;
            $this->reset(['khqrString', 'khqrMd5', 'khqrStringRaw', 'timeLeft', 'paymentStartedAtTs', 'orderProcessing']);
            session()->flash('error', $reason);
        }

        // -------------------------------------------------------------------------
        // Database
        // -------------------------------------------------------------------------

        private function finalizeOrderInDatabase(
            string  $paymentStatus,
            string  $orderStatus,
            ?string $transactionId = null,
        ): Order {
            return DB::transaction(function () use ($paymentStatus, $orderStatus, $transactionId) {
                $customer             = auth('customer')->user();
                $shippingData         = $this->resolveShippingData($customer);
                $selectedShipping     = $this->resolveShippingMethod();
                $subtotal             = $this->getSubtotal();
                $shippingCost         = $this->getShippingCost();

                $order = Order::create([
                    'order_number'    => 'ORD-' . strtoupper(uniqid()),
                    'customer_id'     => $customer->id,
                    'subtotal'        => $subtotal,
                    'discount_amount' => 0,
                    'shipping_cost'   => $shippingCost,
                    'tax_amount'      => 0,
                    'total'           => $subtotal + $shippingCost,
                    'shipping_method' => $selectedShipping->name,
                    'payment_method'  => $this->paymentMethod,
                    'payment_status'  => $paymentStatus,
                    'status'          => $orderStatus,
                    'transaction_id'  => $transactionId,
                    'customer_notes'  => $this->customerNotes,
                    ...$shippingData,
                ]);

                $this->createOrderItems($order);

                if ($paymentStatus === 'paid') {
                    app(OrderStockService::class)->deductForPaidOrder($order);
                }

                session()->forget('cart');

                return $order;
            });
        }

        private function resolveShippingData(mixed $customer): array
        {
            if ($this->useExistingAddress && $this->selectedAddressId) {
                $address = Address::findOrFail($this->selectedAddressId);

                return [
                    'shipping_full_name'      => $address->full_name,
                    'shipping_phone'          => $address->phone,
                    'shipping_address_line_1' => $address->address_line_1,
                    'shipping_address_line_2' => $address->address_line_2,
                    'shipping_city'           => $address->city,
                    'shipping_state'          => $address->state,
                    'shipping_postal_code'    => $address->postal_code ?? '',
                    'shipping_country'        => $address->country,
                ];
            }

            if ($this->saveAddress) {
                $customer->address()->create([
                    'full_name'      => $this->full_name,
                    'phone'          => $this->phone,
                    'address_line_1' => $this->address_line_1,
                    'address_line_2' => $this->address_line_2,
                    'city'           => $this->city,
                    'state'          => $this->state,
                    'country'        => $this->country,
                    'is_default'     => ! $customer->address()->exists(),
                ]);
            }

            return [
                'shipping_full_name'      => $this->full_name,
                'shipping_phone'          => $this->phone,
                'shipping_address_line_1' => $this->address_line_1,
                'shipping_address_line_2' => $this->address_line_2,
                'shipping_city'           => $this->city,
                'shipping_state'          => $this->state,
                'shipping_postal_code'    => '',
                'shipping_country'        => $this->country,
            ];
        }

        private function resolveShippingMethod(): ShippingMethod
        {
            $method = ShippingMethod::query()
                ->active()
                ->whereKey($this->selectedShippingMethodId)
                ->first();

            if (! $method) {
                throw new \RuntimeException('Selected shipping method is not available.');
            }

            return $method;
        }

        private function createOrderItems(Order $order): void
        {
            foreach ($this->cart as $item) {
                $product = Product::query()->lockForUpdate()->find($item['product_id']);

                if (! $product) {
                    throw new \RuntimeException('Product not found.');
                }

                $quantity = (int) $item['quantity'];

                if (! $product->isAvailableForPurchase()) {
                    throw ValidationException::withMessages([
                        'cart' => "{$product->name} is out of stock.",
                    ]);
                }

                if ($quantity < 1 || $quantity > (int) $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Only {$product->stock_quantity} {$product->name} items are available.",
                    ]);
                }

                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => $item['name'],
                    'product_sku'  => $product->sku,
                    'quantity'     => $quantity,
                    'unit_amount'  => $item['price'],
                    'total_amount' => $item['price'] * $quantity,
                ]);
            }
        }

        private function validateCartAvailability(): bool
        {
            foreach ($this->cart as $item) {
                $product = Product::query()->find($item['product_id']);

                if (! $product || ! $product->isAvailableForPurchase()) {
                    session()->flash('error', ($item['name'] ?? 'A product').' is currently out of stock.');

                    return false;
                }

                if ((int) $item['quantity'] < 1 || (int) $item['quantity'] > (int) $product->stock_quantity) {
                    session()->flash('error', 'Only '.$product->stock_quantity.' '.$product->name.' items are available.');

                    return false;
                }
            }

            return true;
        }

        // -------------------------------------------------------------------------
        // Helpers
        // -------------------------------------------------------------------------

        protected function getSubtotal(): float
        {
            return (float) array_sum(
                array_map(fn ($item) => $item['price'] * $item['quantity'], $this->cart)
            );
        }

        protected function getShippingCost(): float
        {
            $method = ShippingMethod::query()
                ->active()
                ->whereKey($this->selectedShippingMethodId)
                ->first();

            return $method ? (float) $method->cost : 0.0;
        }
    };
?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>

        {{-- Progress Steps --}}
        <div class="mb-8 px-2 sm:px-0">
            <div class="flex items-center justify-between w-full max-w-3xl mx-auto">

                <div class="flex flex-col sm:flex-row items-center {{ $step >= 1 ? 'text-blue-600' : 'text-gray-400' }}">
                    <div class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 {{ $step >= 1 ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white' }}">
                        @if ($step > 1)
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            1
                        @endif
                    </div>
                    <span class="mt-2 sm:mt-0 sm:ml-2 text-xs sm:text-sm font-medium text-center">Shipping</span>
                </div>

                <div class="flex-1 h-1 mx-2 sm:mx-4 rounded {{ $step >= 2 ? 'bg-blue-600' : 'bg-gray-300' }}"></div>

                <div class="flex flex-col sm:flex-row items-center {{ $step >= 2 ? 'text-blue-600' : 'text-gray-400' }}">
                    <div class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 {{ $step >= 2 ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white' }}">
                        @if ($step > 2)
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            2
                        @endif
                    </div>
                    <span class="mt-2 sm:mt-0 sm:ml-2 text-xs sm:text-sm font-medium text-center">Review</span>
                </div>

                <div class="flex-1 h-1 mx-2 sm:mx-4 rounded {{ $step >= 3 ? 'bg-blue-600' : 'bg-gray-300' }}"></div>

                <div class="flex flex-col sm:flex-row items-center {{ $step >= 3 ? 'text-blue-600' : 'text-gray-400' }}">
                    <div class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 {{ $step >= 3 ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white' }}">
                        3
                    </div>
                    <span class="mt-2 sm:mt-0 sm:ml-2 text-xs sm:text-sm font-medium text-center">Payment</span>
                </div>

            </div>
        </div>

        <div class="lg:grid lg:grid-cols-3 lg:gap-8">

            {{-- ── Main Content ── --}}
            <div class="lg:col-span-2">

                @if (session()->has('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        {{ session('error') }}
                    </div>
                @endif

                @if (session()->has('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- ── Step 1: Shipping Address ── --}}
                @if ($step === 1)
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Shipping Address</h2>

                        @if ($this->addresses->count() > 0)
                            <div class="mb-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="useExistingAddress" class="w-4 h-4 text-blue-600 rounded">
                                    <span class="font-medium">Use saved address</span>
                                </label>
                            </div>

                            @if ($useExistingAddress)
                                <div class="grid gap-4 mb-6">
                                    @foreach ($this->addresses as $address)
                                        <label class="relative cursor-pointer">
                                            <input type="radio" wire:model="selectedAddressId" value="{{ $address->id }}" class="peer sr-only">
                                            <div class="border-2 rounded-xl p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-400 transition">
                                                <p class="font-semibold text-gray-900">{{ $address->full_name }}</p>
                                                <p class="text-gray-600 text-sm">{{ $address->phone }}</p>
                                                <p class="text-gray-600 text-sm mt-1">{{ $address->full_address }}</p>
                                                @if ($address->is_default)
                                                    <span class="inline-block mt-2 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Default</span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if (!$useExistingAddress || $this->addresses->count() === 0)
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                    <input type="text" wire:model="full_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    @error('full_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                                    <input type="tel" wire:model="phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1 *</label>
                                    <input type="text" wire:model="address_line_1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    @error('address_line_1') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                                    <input type="text" wire:model="address_line_2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">City/Province *</label>
                                        <input type="text" wire:model="city" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        @error('city') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">District/Khan</label>
                                        <input type="text" wire:model="state" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
                                    <select wire:model="country" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="KH">Cambodia</option>
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                        <option value="UK">United Kingdom</option>
                                    </select>
                                    <div class="mt-4 pt-4 border-t">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="saveAddress" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                            <span class="font-medium text-gray-700">Save this address for future orders</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6 pt-6 border-t">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Shipping Method</h3>
                            @if ($this->shippingMethods->isNotEmpty())
                                <div class="grid gap-3">
                                    @foreach ($this->shippingMethods as $shippingMethod)
                                        <label class="relative cursor-pointer" wire:key="shipping-method-{{ $shippingMethod->id }}">
                                            <input type="radio" wire:model.live="selectedShippingMethodId" value="{{ $shippingMethod->id }}" class="peer sr-only">
                                            <div class="border-2 rounded-xl p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-400 transition">
                                                <div class="flex items-center justify-between">
                                                    <p class="font-semibold text-gray-900">{{ $shippingMethod->name }}</p>
                                                    <p class="font-semibold text-blue-700">${{ number_format($shippingMethod->cost, 2) }}</p>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-red-600">No active shipping methods are available right now.</p>
                            @endif
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row justify-between items-center gap-4 mt-8 pt-6 border-t w-full">
                            <a wire:navigate href="{{ route('cart.index') }}" class="w-full sm:w-auto text-center text-gray-600 hover:text-gray-900 font-medium py-2">
                                ← Back to Cart
                            </a>
                            <button wire:click="nextStep" class="w-full sm:w-auto bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition font-semibold">
                                Continue to Review
                            </button>
                        </div>
                    </div>
                @endif

                {{-- ── Step 2: Review Order ── --}}
                @if ($step === 2)
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Review Your Order</h2>

                        <div class="space-y-4 mb-6">
                            @foreach ($cart as $item)
                                <div class="flex gap-4 pb-4 border-b">
                                    <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                        @if (!empty($item['image']))
                                            <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900">{{ $item['name'] }}</h3>
                                        <p class="text-sm text-gray-600">Quantity: {{ $item['quantity'] }}</p>
                                        <p class="text-sm text-gray-600">Price: ${{ number_format($item['price'], 2) }} each</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-gray-900">${{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Order Notes (Optional)</label>
                            <textarea wire:model="customerNotes" rows="3" placeholder="Special instructions for your order..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row justify-between items-center gap-4 mt-8 pt-6 border-t w-full">
                            <button wire:click="previousStep" class="w-full sm:w-auto text-center text-gray-600 hover:text-gray-900 font-medium py-2">
                                ← Back to Shipping
                            </button>
                            <button wire:click="nextStep" class="w-full sm:w-auto bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition font-semibold">
                                Continue to Payment
                            </button>
                        </div>
                    </div>
                @endif

                {{-- ── Step 3: Payment ── --}}
                @if ($step === 3)
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Payment Method</h2>

                        <div class="space-y-4 mb-6">

                            {{-- Cash on Delivery --}}
                            <label class="relative cursor-pointer block">
                                <input type="radio"
                                    wire:model="paymentMethod"
                                    value="cash_on_delivery"
                                    class="peer sr-only">

                                <div class="relative overflow-hidden rounded-2xl border-2 border-gray-200 bg-white p-4
                                            transition-all duration-200
                                            hover:border-blue-300 hover:shadow-md
                                            peer-checked:border-[#2563EB]
                                            peer-checked:bg-gradient-to-br peer-checked:from-blue-50 peer-checked:to-white
                                            peer-checked:shadow-lg">

                                    {{-- Active Indicator --}}
                                    <div class="absolute top-3 right-3 hidden peer-checked:flex items-center justify-center
                                                w-6 h-6 rounded-full bg-[#2563EB] text-white shadow">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="3">
                                            <path stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>

                                    <div class="flex items-center gap-4">

                                        {{-- Icon --}}
                                        <div class="flex-shrink-0">
                                            <div class="w-14 h-14 rounded-2xl bg-blue-100 border border-blue-200
                                                        shadow-sm flex items-center justify-center">

                                                <svg class="w-7 h-7 text-[#2563EB]"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>

                                            </div>
                                        </div>

                                        {{-- Content --}}
                                        <div class="flex-1">

                                            <div class="flex items-center gap-2">

                                                <p class="text-base font-bold text-gray-900">
                                                    Cash on Delivery
                                                </p>
                                            </div>

                                            <p class="mt-1 text-sm text-gray-500 leading-relaxed">
                                                Pay safely when your order arrives at your doorstep.
                                            </p>

                                        </div>
                                    </div>
                                </div>
                            </label>

                            {{-- KHQR --}}
                            <label class="relative cursor-pointer block">
                                <input type="radio"
                                    wire:model="paymentMethod"
                                    value="KHQR"
                                    class="peer sr-only">

                                <div class="relative overflow-hidden rounded-2xl border-2 border-gray-200 bg-white p-4
                                            transition-all duration-200
                                            hover:border-red-300 hover:shadow-md
                                            peer-checked:border-[#ED1C24]
                                            peer-checked:bg-gradient-to-br peer-checked:from-red-50 peer-checked:to-white
                                            peer-checked:shadow-lg">

                                    {{-- Active Indicator --}}
                                    <div class="absolute top-3 right-3 hidden peer-checked:flex items-center justify-center
                                                w-6 h-6 rounded-full bg-[#ED1C24] text-white shadow">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="3">
                                            <path stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>

                                    <div class="flex items-center gap-4">

                                        {{-- KHQR Logo --}}
                                        <div class="flex-shrink-0">
                                            <div class="w-14 h-14 rounded-2xl bg-white border border-red-100 shadow-sm flex items-center justify-center p-2">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/b/bb/KHQR_Logo.png"
                                                    alt="KHQR"
                                                    class="w-full h-full object-contain">
                                            </div>
                                        </div>

                                        {{-- Content --}}
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">

                                                <p class="text-base font-bold text-gray-900">
                                                    KHQR Payment
                                                </p>
                                            </div>

                                            <p class="mt-1 text-sm text-gray-500 leading-relaxed">
                                                Scan securely using ABA, ACLEDA, Wing, Bakong,
                                                or any Cambodian banking app.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </label>

                        </div>

                        <div class="flex flex-col-reverse sm:flex-row justify-between items-center gap-4 mt-8 pt-6 border-t w-full">
                            <button wire:click="previousStep" class="w-full sm:w-auto text-center text-gray-600 hover:text-gray-900 font-medium py-2">
                                ← Back to Review
                            </button>
                            <button wire:click="placeOrder"
                                wire:loading.attr="disabled"
                                class="w-full sm:w-auto px-8 py-3 rounded-xl font-bold transition-all duration-200 shadow-md hover:shadow-lg active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed
                                {{ $paymentMethod === 'KHQR'
                                    ? 'bg-[#ED1C24] hover:bg-[#d91920] text-white'
                                    : 'bg-[#2563EB] hover:bg-[#1d4ed8] text-white' }}">

                                <div class="flex items-center justify-center gap-2">

                                    {{-- Loading spinner (shown while placeOrder is running) --}}
                                    <svg wire:loading wire:target="placeOrder"
                                        class="animate-spin w-5 h-5"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>

                                    @if($paymentMethod === 'KHQR')
                                        {{-- QR Icon (hide while loading) --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" wire:loading.remove wire:target="placeOrder"
                                            class="w-5 h-5"
                                            fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 4h5v5H4V4zm11 0h5v5h-5V4zM4 15h5v5H4v-5zm8-8h1m3 3h1m-5 5h1m3 0h1m-4 4h5"/>
                                        </svg>

                                        <span>
                                            <span wire:loading.remove wire:target="placeOrder">Scan & Pay with KHQR</span>
                                            <span wire:loading wire:target="placeOrder">Processing…</span>
                                        </span>
                                    @else
                                        {{-- Cart Icon (hide while loading) --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" wire:loading.remove wire:target="placeOrder"
                                            class="w-5 h-5"
                                            fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L5.4 5M7 13l-1 5h12m-9 0a1 1 0 102 0m6 0a1 1 0 102 0"/>
                                        </svg>

                                        <span>
                                            <span wire:loading.remove wire:target="placeOrder">Place Order</span>
                                            <span wire:loading wire:target="placeOrder">Processing…</span>
                                        </span>
                                    @endif

                                </div>
                            </button>
                        </div>
                    </div>
                @endif

            </div>

            {{-- ── Order Summary sidebar ── --}}
            <div class="lg:col-span-1 mt-6 lg:mt-0">
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-24">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h2>

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium">${{ number_format($this->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Shipping</span>
                            <span class="font-medium">
                                @if ($this->shippingCost > 0)
                                    ${{ number_format($this->shippingCost, 2) }}
                                @else
                                    <span class="text-green-600">FREE</span>
                                @endif
                            </span>
                        </div>
                        @if ($selectedShippingMethodId)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Method</span>
                                <span class="font-medium text-gray-700">
                                    {{ optional($this->shippingMethods->firstWhere('id', (int) $selectedShippingMethodId))->name ?? 'Not selected' }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold">Total</span>
                            <span class="text-2xl font-bold text-blue-600">${{ number_format($this->total, 2) }}</span>
                        </div>
                    </div>

                    @if ($step >= 2)
                        <div class="mt-6 pt-6 border-t">
                            <h3 class="font-semibold text-gray-900 mb-2">Shipping To:</h3>
                            @if ($useExistingAddress && $selectedAddressId)
                                @php $address = $this->addresses->firstWhere('id', $selectedAddressId); @endphp
                                @if ($address)
                                    <p class="text-sm text-gray-600">{{ $address->full_name }}</p>
                                    <p class="text-sm text-gray-600">{{ $address->phone }}</p>
                                    <p class="text-sm text-gray-600">{{ $address->full_address }}</p>
                                @endif
                            @else
                                <p class="text-sm text-gray-600">{{ $full_name }}</p>
                                <p class="text-sm text-gray-600">{{ $phone }}</p>
                                <p class="text-sm text-gray-600">
                                    {{ $address_line_1 }}<br>
                                    @if ($address_line_2) {{ $address_line_2 }}<br> @endif
                                    {{ $city }}@if ($state), {{ $state }}@endif<br>
                                    {{ $country }}
                                </p>
                            @endif
                        </div>
                    @endif

                </div>
            </div>

        </div>{{-- end grid --}}
    </div>{{-- end max-w-7xl --}}

    {{-- ── KHQR Modal — at root level so it overlays everything ── --}}
    @if ($showKhqrModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0,0,0,0.45);">

            <div class="absolute inset-0" wire:click="cancelPayment('Payment cancelled')"></div>

            <div class="relative z-10 overflow-hidden"
                style="width:305px;background:#fff;border-radius:16px;box-shadow:0 10px 35px rgba(0,0,0,.15);">

                <div wire:poll.1s="checkKhqrStatus">

                    {{-- Header --}}
                    <div class="flex items-center justify-center"
                        style="height:54px;background:#ed1c24;">
                        <div style="font-size:22px;font-weight:900;color:white;letter-spacing:1px;font-family:Arial,sans-serif;">
                            KHQR
                        </div>
                    </div>

                    {{-- Merchant + Amount --}}
                    <div style="padding:24px 42px 22px;">
                        <p style="margin:0;font-size:13px;color:#222;font-family:Arial,sans-serif;">
                            {{ $merchantName }}
                        </p>

                        <div style="display:flex;align-items:flex-end;gap:8px;margin-top:3px;">
                            <span style="font-size:30px;font-weight:900;color:#000;line-height:1;font-family:Arial,sans-serif;">
                                {{ number_format($this->total, 0) }}
                            </span>
                            <span style="font-size:13px;color:#222;font-family:Arial,sans-serif;">
                                KHR
                            </span>
                        </div>
                    </div>

                    {{-- Dashed divider --}}
                    <div style="border-top:1.5px dashed #cfcfcf;"></div>

                    {{-- QR --}}
                    <div style="padding:36px 42px 28px;display:flex;justify-content:center;align-items:center;">
                        @if ($khqrString)
                            <div style="width:215px;height:215px;display:flex;align-items:center;justify-content:center;">
                                {!! $khqrString !!}
                            </div>
                        @endif
                    </div>

                    {{-- Waiting + Timer --}}
                    <div style="padding:0 24px 16px;text-align:center;">
                        <p style="margin:0 0 6px;font-size:12px;color:#888;font-family:Arial,sans-serif;">
                            Waiting for payment...
                        </p>

                        <div style="font-size:22px;font-weight:800;color:{{ $timeLeft < 60 ? '#ed1c24' : '#2563eb' }};font-family:monospace;">
                            {{ sprintf('%02d:%02d', floor($timeLeft / 60), $timeLeft % 60) }}
                        </div>
                    </div>

                    {{-- Cancel --}}
                    <div style="padding:0 24px 22px;">
                        <button type="button"
                                wire:click="cancelPayment('Payment cancelled by customer')"
                                style="width:100%;padding:11px;border:1.5px solid #ed1c24;border-radius:10px;background:white;color:#ed1c24;font-size:13px;font-weight:700;cursor:pointer;">
                            Cancel Order
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif

 </div>
