<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\OrderItem;
use App\Services\OrderStockService;
use Illuminate\Support\Facades\DB;

use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;



use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

new class extends Component {
    public $cart = [];
    public $step = 1; // 1: Address, 2: Review, 3: Payment

    // Address fields
    public $useExistingAddress = true;
    public $saveAddress = false;
    public $selectedAddressId = null;
    public $full_name = '';
    public $phone = '';
    public $address_line_1 = '';
    public $address_line_2 = '';
    public $city = '';
    public $state = '';
    public $country = 'KH'; // Cambodia country code

    // Order details
    public $paymentMethod = 'KHQR';
    public $customerNotes = '';
    public $selectedShippingMethodId = null;

    public $showKhqrModal = false;
    public $khqrString = '';
    public $khqrMd5 = null;
    public $khqrStringRaw = null;
    public $orderProcessing = false;

    public $paymentTimeout = 120; // 5 minutes in seconds
    public $paymentStartedAtTs = null;
    public $timeLeft = 120;

    public function mount()
    {
        $this->cart = session()->get('cart', []);

        if (empty($this->cart)) {
            return redirect()->route('cart.index');
        }

        $customer = auth('customer')->user();
        $this->full_name = $customer->name;
        $this->phone = $customer->phone ?? '';

        // Set default address if available
        $defaultAddress = $customer->address()->where('is_default', true)->first();
        if ($defaultAddress) {
            $this->selectedAddressId = $defaultAddress->id;
        } else {
            // If no default address, select the first one if exists
            $firstAddress = $customer->address()->first();
            if ($firstAddress) {
                $this->selectedAddressId = $firstAddress->id;
            } else {
                // If no addresses at all, switch to new address form
                $this->useExistingAddress = false;
            }
        }

        $this->selectedShippingMethodId = $this->shippingMethods->first()?->id;
    }

    #[Computed]
    public function addresses()
    {
        return auth('customer')->user()->address ?? collect();
    }

    #[Computed]
    public function subtotal()
    {
        return $this->getSubtotal();
    }

    #[Computed]
    public function shippingCost()
    {
        return $this->getShippingCost();
    }

    #[Computed]
    public function total()
    {
        return $this->subtotal + $this->shippingCost;
    }

    #[Computed]
    public function shippingMethods()
    {
        return ShippingMethod::query()->active()->orderBy('name')->get();
    }

    public function selectAddress($addressId)
    {
        $this->selectedAddressId = $addressId;
    }

    public function nextStep()
    {
        if ($this->step === 1) {
            if ($this->validateAddress() && $this->validateShippingMethod()) {
                $this->step = 2;
            }
        } elseif ($this->step === 2) {
            $this->step = 3;
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    protected function validateAddress()
    {
        if (!$this->useExistingAddress) {
            // Validate new address form
            $this->validate([
                'full_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'address_line_1' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'country' => 'required|string|max:2',
            ]);

            return true;
        } else {
            // Validate existing address selection
            if (!$this->selectedAddressId) {
                // Check if there are any addresses available
                $addressCount = auth('customer')->user()->address()->count();

                if ($addressCount === 0) {
                    // No addresses available, switch to new address form
                    $this->useExistingAddress = false;
                    session()->flash('error', 'No saved addresses found. Please add a new address.');
                    return false;
                } else {
                    // Addresses exist but none selected
                    session()->flash('error', 'Please select a shipping address.');
                    return false;
                }
            }

            // Verify the selected address exists and belongs to the customer
            $address = Address::where('id', $this->selectedAddressId)
                ->where('customer_id', auth('customer')->id())
                ->first();

            if (!$address) {
                session()->flash('error', 'Invalid address selected.');
                return false;
            }

            return true;
        }
    }

    protected function validateShippingMethod()
    {
        if ($this->shippingMethods->isEmpty()) {
            session()->flash('error', 'No active shipping methods are available right now.');
            return false;
        }

        $shippingMethod = ShippingMethod::query()->active()->whereKey($this->selectedShippingMethodId)->first();

        if (!$shippingMethod) {
            session()->flash('error', 'Please select an active shipping method.');
            return false;
        }

        return true;
    }

    public function placeOrder()
    {
        // Validate address again before placing order
        if (!$this->validateAddress() || !$this->validateShippingMethod()) {
            $this->step = 1; // Go back to address step
            return;
        }

        if ($this->paymentMethod === 'KHQR') {
            // Wait to save! Just generate the QR code first.
            $this->processKhqrPayment();
        } else {
            // Cash on Delivery - Save order immediately
            try {
                $order = $this->finalizeOrderInDatabase('pending', 'pending');

                // REDIRECT UPDATED HERE FOR COD
                return redirect()->route('checkout.success', $order->id)->with('success', 'Order placed successfully!');
            } catch (\Exception $e) {
                session()->flash('error', 'Error placing order: ' . $e->getMessage());
            }
        }
    }

    // Extracted database logic so it can be called exactly when needed
    private function finalizeOrderInDatabase($paymentStatus, $orderStatus, $transactionId = null)
    {
        DB::beginTransaction();

        $customer = auth('customer')->user();

        // Get shipping address data
        if ($this->useExistingAddress && $this->selectedAddressId) {
            $address = Address::find($this->selectedAddressId);
            $shippingData = [
                'shipping_full_name' => $address->full_name,
                'shipping_phone' => $address->phone,
                'shipping_address_line_1' => $address->address_line_1,
                'shipping_address_line_2' => $address->address_line_2,
                'shipping_city' => $address->city,
                'shipping_state' => $address->state,
                'shipping_postal_code' => $address->postal_code ?? '',
                'shipping_country' => $address->country,
            ];
        } else {
            $shippingData = [
                'shipping_full_name' => $this->full_name,
                'shipping_phone' => $this->phone,
                'shipping_address_line_1' => $this->address_line_1,
                'shipping_address_line_2' => $this->address_line_2,
                'shipping_city' => $this->city,
                'shipping_state' => $this->state,
                'shipping_postal_code' => '',
                'shipping_country' => $this->country,
            ];
            if ($this->saveAddress) {
                $customer->address()->create([
                    'full_name' => $this->full_name,
                    'phone' => $this->phone,
                    'address_line_1' => $this->address_line_1,
                    'address_line_2' => $this->address_line_2,
                    'city' => $this->city,
                    'state' => $this->state,
                    'country' => $this->country,
                    // If this is their first ever address, automatically make it the default!
                    'is_default' => $customer->address()->count() === 0,
                ]);
            }
        }

        $selectedShippingMethod = ShippingMethod::query()->active()->whereKey($this->selectedShippingMethodId)->first();

        if (!$selectedShippingMethod) {
            DB::rollBack();
            throw new \RuntimeException('Selected shipping method is not available.');
        }

        // Calculate totals
        $subtotal = $this->getSubtotal();
        $shippingCost = $this->getShippingCost();
        $taxAmount = 0;
        $total = $subtotal + $shippingCost + $taxAmount;

        $orderNumber = 'ORD-' . strtoupper(uniqid());

        // Create order
        $order = Order::create(
            [
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'shipping_method' => $selectedShippingMethod->name,
                'payment_method' => $this->paymentMethod,
                'payment_status' => $paymentStatus,
                'status' => $orderStatus,
                'transaction_id' => $transactionId, // Save Bakong MD5 if paid
                'customer_notes' => $this->customerNotes,
            ] + $shippingData,
        );

        // Create order items
        foreach ($this->cart as $item) {
            $product = Product::query()->lockForUpdate()->find($item['product_id']);

            if (!$product) {
                DB::rollBack();
                throw new \RuntimeException('Product not found.');
            }

            $unitAmount = $item['price'];
            $quantity = (int) $item['quantity'];
            $totalAmount = $unitAmount * $quantity;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $item['name'],
                'product_sku' => $product->sku,
                'quantity' => $quantity,
                'unit_amount' => $unitAmount,
                'total_amount' => $totalAmount,
            ]);
        }

        if ($paymentStatus === 'paid') {
            app(OrderStockService::class)->deductForPaidOrder($order);
        }

        DB::commit();

        // Clear cart
        session()->forget('cart');

        return $order;
    }

    protected function processKhqrPayment()
    {
$expirationTimestamp = (time() + 120) * 1000;
        try {
$merchant = new IndividualInfo(
    bakongAccountID: env('BAKONG_MERCHANT_ID'),
    merchantName: env('BAKONG_MERCHANT_NAME'),
    merchantCity: env('BAKONG_MERCHANT_CITY'),
    currency: KHQRData::CURRENCY_USD,
    amount: (float) $this->total,
    expirationTimestamp:$expirationTimestamp,
);

            $qrResponse = BakongKHQR::generateIndividual($merchant);

            if (isset($qrResponse->data['qr']) && isset($qrResponse->data['md5'])) {
                $this->khqrStringRaw = $qrResponse->data['qr'];
                $this->khqrMd5 = $qrResponse->data['md5'];
                $this->orderProcessing = false;

                $this->paymentStartedAtTs = now()->timestamp;
                $this->timeLeft = $this->paymentTimeout;

                $renderer = new ImageRenderer(new RendererStyle(250), new SvgImageBackEnd());
                $writer = new Writer($renderer);

                $fullSvg = $writer->writeString($this->khqrStringRaw);
                $this->khqrString = trim(substr($fullSvg, strpos($fullSvg, '<svg')));

                $this->showKhqrModal = true;
            } else {
                session()->flash('error', 'Failed to generate KHQR code.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error generating payment: ' . $e->getMessage());
        }
    }

    public function checkKhqrStatus()
    {
        if (!$this->khqrMd5 || !$this->paymentStartedAtTs) {
            return;
        }

        if ($this->orderProcessing) {
            return;
        }

        $elapsed = now()->timestamp - $this->paymentStartedAtTs;
        $this->timeLeft = max(0, $this->paymentTimeout - $elapsed);

        if ($this->timeLeft <= 0) {
            $this->cancelPayment('Payment timed out.');
            return;
        }

        try {
            $token = env('BAKONG_TOKEN');
            $bakong = new BakongKHQR($token);
            $result = $bakong->checkTransactionByMD5($this->khqrMd5);

            // If payment is SUCCESSFUL
            if (isset($result['responseCode']) && $result['responseCode'] === 0) {
                $this->orderProcessing = true;

                $existingOrder = Order::query()->where('transaction_id', $this->khqrMd5)->first();

                if ($existingOrder) {
                    $this->showKhqrModal = false;

                    return redirect()->route('checkout.success', $existingOrder->id);
                }

                // ONLY NOW do we insert the order into the database!
                $order = $this->finalizeOrderInDatabase('paid', 'processing', $this->khqrMd5);
                $this->showKhqrModal = false;

                // REDIRECT UPDATED HERE FOR KHQR
                return redirect()->route('checkout.success', $order->id)->with('success', 'Payment successful!');
            }
        } catch (\Exception $e) {
            $this->orderProcessing = false;
            // Log error silently while polling
        }
    }

    public function cancelPayment($reason = 'Payment cancelled by user.')
    {
        // Close modal and clear QR variables WITHOUT saving to database
        $this->showKhqrModal = false;
        $this->reset(['khqrString', 'khqrMd5', 'timeLeft', 'paymentStartedAtTs', 'orderProcessing']);
        session()->flash('error', $reason);
    }

    protected function getSubtotal()

    {
        return array_sum(
            array_map(function ($item) {
                return $item['price'] * $item['quantity'];
            }, $this->cart),
        );
    }

    protected function getShippingCost()
    {
        $shippingMethod = ShippingMethod::query()->active()->whereKey($this->selectedShippingMethodId)->first();

        if (!$shippingMethod) {
            return 0;
        }

        return (float) $shippingMethod->cost;
    }
};
?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>

        <!-- Progress Steps -->
        <div class="mb-8 px-2 sm:px-0">
            <div class="flex items-center justify-between w-full max-w-3xl mx-auto">

                <div class="flex flex-col sm:flex-row items-center {{ $step >= 1 ? 'text-blue-600' : 'text-gray-400' }}">
                    <div
                        class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 {{ $step >= 1 ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white' }}">
                        @if ($step > 1)
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            1
                        @endif
                    </div>
                    <span class="mt-2 sm:mt-0 sm:ml-2 text-xs sm:text-sm font-medium text-center">Shipping</span>
                </div>

                <div class="flex-1 h-1 mx-2 sm:mx-4 rounded {{ $step >= 2 ? 'bg-blue-600' : 'bg-gray-300' }}"></div>

                <div
                    class="flex flex-col sm:flex-row items-center {{ $step >= 2 ? 'text-blue-600' : 'text-gray-400' }}">
                    <div
                        class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 {{ $step >= 2 ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white' }}">
                        @if ($step > 2)
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            2
                        @endif
                    </div>
                    <span class="mt-2 sm:mt-0 sm:ml-2 text-xs sm:text-sm font-medium text-center">Review</span>
                </div>

                <div class="flex-1 h-1 mx-2 sm:mx-4 rounded {{ $step >= 3 ? 'bg-blue-600' : 'bg-gray-300' }}"></div>

                <div
                    class="flex flex-col sm:flex-row items-center {{ $step >= 3 ? 'text-blue-600' : 'text-gray-400' }}">
                    <div
                        class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full border-2 {{ $step >= 3 ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white' }}">
                        3
                    </div>
                    <span class="mt-2 sm:mt-0 sm:ml-2 text-xs sm:text-sm font-medium text-center">Payment</span>
                </div>

            </div>
        </div>

        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
            <!-- Main Content -->
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

                <!-- Step 1: Shipping Address -->
                @if ($step === 1)
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Shipping Address</h2>

                        <!-- Use Existing Address -->
                        @if ($this->addresses->count() > 0)
                            <div class="mb-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="useExistingAddress"
                                        class="w-4 h-4 text-blue-600 rounded">
                                    <span class="font-medium">Use saved address</span>
                                </label>
                            </div>

                            @if ($useExistingAddress)
                                <div class="grid gap-4 mb-6">
                                    @foreach ($this->addresses as $address)
                                        <label class="relative cursor-pointer">
                                            <input type="radio" wire:model="selectedAddressId"
                                                value="{{ $address->id }}" class="peer sr-only">
                                            <div
                                                class="border-2 rounded-lg p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-400 transition">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <p class="font-semibold text-gray-900">
                                                            {{ $address->full_name }}</p>
                                                        <p class="text-gray-600">{{ $address->phone }}</p>
                                                        <p class="text-gray-600 mt-2">
                                                            {{ $address->full_address }}
                                                        </p>
                                                        @if ($address->is_default)
                                                            <span
                                                                class="inline-block mt-2 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                                                Default
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        <!-- New Address Form -->
                        @if (!$useExistingAddress || $this->addresses->count() === 0)
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                    <input type="text" wire:model="full_name"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    @error('full_name')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                                    <input type="tel" wire:model="phone"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    @error('phone')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1
                                        *</label>
                                    <input type="text" wire:model="address_line_1"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    @error('address_line_1')
                                        <span class="text-red-600 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address Line
                                        2</label>
                                    <input type="text" wire:model="address_line_2"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">City/Province
                                            *</label>
                                        <input type="text" wire:model="city"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        @error('city')
                                            <span class="text-red-600 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2">District/Khan</label>
                                        <input type="text" wire:model="state"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
                                    <select wire:model="country"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="KH">Cambodia</option>
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                        <option value="UK">United Kingdom</option>
                                    </select>
                                    <div class="mt-4 pt-4 border-t">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:model="saveAddress"
                                                class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                            <span class="font-medium text-gray-700">Save this address to my profile for
                                                future orders</span>
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
                                        <label class="relative cursor-pointer"
                                            wire:key="shipping-method-{{ $shippingMethod->id }}">
                                            <input type="radio" wire:model.live="selectedShippingMethodId"
                                                value="{{ $shippingMethod->id }}" class="peer sr-only">
                                            <div
                                                class="border-2 rounded-lg p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-400 transition">
                                                <div class="flex items-center justify-between">
                                                    <p class="font-semibold text-gray-900">{{ $shippingMethod->name }}
                                                    </p>
                                                    <p class="font-semibold text-blue-700">
                                                        ${{ number_format($shippingMethod->cost, 2) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-red-600">No active shipping methods are available right now.</p>
                            @endif
                        </div>

                        <div
                            class="flex flex-col-reverse sm:flex-row justify-between items-center gap-4 mt-8 pt-6 border-t w-full">
                            <a wire:navigate href="{{ route('cart.index') }}"
                                class="w-full sm:w-auto text-center text-gray-600 hover:text-gray-900 font-medium py-2">
                                ← Back to Cart
                            </a>
                            <button wire:click="nextStep"
                                class="w-full sm:w-auto bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition font-semibold">
                                Continue to Review
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Step 2: Review Order -->
                @if ($step === 2)
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Review Your Order</h2>

                        <!-- Order Items -->
                        <div class="space-y-4 mb-6">
                            @foreach ($cart as $item)
                                <div class="flex gap-4 pb-4 border-b">
                                    <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                        @if (!empty($item['image']))
                                            <img src="{{ asset('storage/' . $item['image']) }}"
                                                alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900">{{ $item['name'] }}</h3>

                                        <p class="text-sm text-gray-600">Quantity: {{ $item['quantity'] }}</p>
                                        <p class="text-sm text-gray-600">Price:
                                            ${{ number_format($item['price'], 2) }} each</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-gray-900">
                                            ${{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Customer Notes -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Order Notes
                                (Optional)</label>
                            <textarea wire:model="customerNotes" rows="3" placeholder="Special instructions for your order..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div
                            class="flex flex-col-reverse sm:flex-row justify-between items-center gap-4 mt-8 pt-6 border-t w-full">
                            <button wire:click="previousStep"
                                class="w-full sm:w-auto text-center text-gray-600 hover:text-gray-900 font-medium py-2">
                                ← Back to Shipping
                            </button>
                            <button wire:click="nextStep"
                                class="w-full sm:w-auto bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition font-semibold">
                                Continue to Payment
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Step 3: Payment -->
                @if ($step === 3)
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Payment Method</h2>

                        <div class="space-y-4 mb-6">
                            <label class="relative cursor-pointer">
                                <input type="radio" wire:model="paymentMethod" value="cash_on_delivery"
                                    class="peer sr-only">
                                <div
                                    class="border-2 rounded-lg p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-400 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <div>
                                                <p class="font-semibold text-gray-900">Cash on Delivery</p>
                                                <p class="text-sm text-gray-600">Pay when you receive your order
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" wire:model="paymentMethod" value="KHQR"
                                    class="peer sr-only">
                                <div
                                    class="border-2 rounded-lg p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-400 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div>
                                                <p class="font-semibold text-gray-900">KHQR Payment</p>
                                                <p class="text-sm text-gray-600">Pay using KHQR scanning</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div
                            class="flex flex-col-reverse sm:flex-row justify-between items-center gap-4 mt-8 pt-6 border-t w-full">
                            <button wire:click="previousStep"
                                class="w-full sm:w-auto text-center text-gray-600 hover:text-gray-900 font-medium py-2">
                                ← Back to Review
                            </button>
                            <button wire:click="placeOrder"
                                class="w-full sm:w-auto bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition font-semibold shadow-sm">
                                Place Order
                            </button>
                        </div>
                @endif
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6 sticky top-24">
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
                            <span class="text-2xl font-bold text-blue-600">
                                ${{ number_format($this->total, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Shipping Address Summary (shown on step 2 & 3) -->
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
                                    @if ($address_line_2)
                                        {{ $address_line_2 }}<br>
                                    @endif
                                    {{ $city }}@if ($state)
                                        , {{ $state }}
                                    @endif
                                    <br>
                                    {{ $country }}
                                </p>
                            @endif
                        </div>
                    @endif

                    @if ($showKhqrModal)
                        <div class="fixed inset-0 z-50 overflow-y-auto">
                            <div
                                class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                                    wire:click="cancelPayment('Payment cancelled')"></div>

                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                                <div
                                    class="relative z-10 inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full sm:p-6">
                                    <div wire:poll.1s="checkKhqrStatus">
                                        <div class="text-center">
                                            <h3 class="text-lg font-bold text-gray-900">Scan to Pay (KHQR)</h3>

                                            <!-- Timer Display -->
                                            <div class="mt-2">
                                                <span
                                                    class="text-2xl font-mono font-bold {{ $timeLeft < 60 ? 'text-red-600' : 'text-blue-600' }}">
                                                    {{ sprintf('%02d:%02d', floor($timeLeft / 60), $timeLeft % 60) }}
                                                </span>
                                                <p class="text-xs text-gray-500">Time remaining</p>
                                            </div>

                                            <!-- Progress Bar -->
                                            <div class="w-full bg-gray-200 h-1.5 mt-2 rounded-full overflow-hidden">
                                                <div class="bg-blue-600 h-full transition-all duration-1000"
                                                    style="width: {{ ($timeLeft / $paymentTimeout) * 100 }}%">
                                                </div>
                                            </div>

                                            <!-- QR Code -->
                                            <div class="mt-4 flex justify-center p-2 bg-white border rounded-xl">
                                                @if ($khqrString)
                                                    {!! $khqrString !!}
                                                @endif
                                            </div>

                                            <!-- Amount -->
                                            <div class="mt-2">
                                                <p class="text-lg font-bold">Total:
                                                    ${{ number_format($this->total, 2) }}</p>
                                            </div>

                                            <!-- Status -->
                                            <div class="mt-4">
                                                <div class="flex justify-center items-center space-x-2">
                                                    <div
                                                        class="animate-spin rounded-full h-3 w-3 border-b-2 border-blue-600">
                                                    </div>
                                                    <span class="text-sm text-gray-600">Waiting for
                                                        payment...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cancel Button -->
                                    <div class="mt-6">
                                        <button type="button"
                                            wire:click="cancelPayment('Payment cancelled by customer')"
                                            class="w-full inline-flex justify-center rounded-md border border-red-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-red-700 hover:bg-red-50 focus:outline-none sm:text-sm">
                                            Cancel Order
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
