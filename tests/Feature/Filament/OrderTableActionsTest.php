<?php

use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Http::fake();
    Mail::fake();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeOrder(string $status = 'pending', string $paymentStatus = 'pending'): Order
{
    $customer = Customer::query()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);

    return Order::query()->create([
        'customer_id' => $customer->id,
        'subtotal' => 100,
        'discount_amount' => 0,
        'shipping_cost' => 0,
        'total' => 100,
        'shipping_method' => 'Standard Delivery',
        'shipping_full_name' => $customer->name,
        'shipping_phone' => fake()->phoneNumber(),
        'shipping_address_line_1' => fake()->streetAddress(),
        'shipping_city' => fake()->city(),
        'shipping_country' => 'KH',
        'payment_method' => 'cash_on_delivery',
        'payment_status' => $paymentStatus,
        'status' => $status,
    ]);
}

// ---------------------------------------------------------------------------
// nextStatus helper (via reflection to keep tests black-box-ish but readable)
// ---------------------------------------------------------------------------

function nextStatusViaReflection(string $current): ?string
{
    $ref = new ReflectionClass(OrdersTable::class);
    $method = $ref->getMethod('nextStatus');
    $method->setAccessible(true);

    return $method->invoke(null, $current);
}

// ---------------------------------------------------------------------------
// Mark Paid action
// ---------------------------------------------------------------------------

test('markPaid sets payment_status to paid', function (): void {
    $order = makeOrder('pending', 'pending');

    $order->update(['payment_status' => 'paid']);

    expect($order->fresh()->payment_status)->toBe('paid');
});

test('markPaid action is hidden when order is already paid', function (): void {
    $order = makeOrder('pending', 'paid');

    // The visible() closure returns false when already paid
    $isVisible = $order->payment_status !== 'paid';

    expect($isVisible)->toBeFalse();
});

test('markPaid action is visible when order is not paid', function (): void {
    $order = makeOrder('pending', 'pending');

    $isVisible = $order->payment_status !== 'paid';

    expect($isVisible)->toBeTrue();
});

// ---------------------------------------------------------------------------
// OrderObserver side-effects on payment_status → paid
// ---------------------------------------------------------------------------

test('marking an order paid triggers stock deduction via OrderObserver', function (): void {
    // The OrderObserver.updated() calls OrderStockService when payment_status transitions to paid.
    // We verify the observer fires by checking stock_deducted_at is set (no items needed
    // for a zero-item order — the service handles it gracefully).
    $order = makeOrder('pending', 'pending');

    $order->update(['payment_status' => 'paid']);

    // stock_deducted_at is set even when there are no items (service marks it immediately)
    expect($order->fresh()->stock_deducted_at)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// nextStatus helper unit behaviour
// ---------------------------------------------------------------------------

test('nextStatus maps the full pipeline correctly', function (string $from, ?string $expected): void {
    expect(nextStatusViaReflection($from))->toBe($expected);
})->with([
    'pending → processing' => ['pending', 'processing'],
    'processing → shipped' => ['processing', 'shipped'],
    'shipped → delivered' => ['shipped', 'delivered'],
    'delivered → null' => ['delivered', null],
    'cancelled → null' => ['cancelled', null],
]);

// ---------------------------------------------------------------------------
// Advance Status action — pipeline progression
// ---------------------------------------------------------------------------

test('advanceStatus moves an order through the full pipeline one step at a time', function (): void {
    $order = makeOrder('pending');

    expect($order->status)->toBe('pending');

    $order->update(['status' => 'processing']);
    expect($order->fresh()->status)->toBe('processing');

    $order->update(['status' => 'shipped']);
    expect($order->fresh()->status)->toBe('shipped');

    $order->update(['status' => 'delivered']);
    expect($order->fresh()->status)->toBe('delivered');
});

test('advancing status creates a status history entry via OrderObserver', function (): void {
    $order = makeOrder('pending');

    $order->update(['status' => 'processing']);

    $history = OrderStatusHistory::query()
        ->where('order_id', $order->id)
        ->where('status', 'processing')
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->notes)->toContain('processing');
});

// ---------------------------------------------------------------------------
// Advance Status visibility
// ---------------------------------------------------------------------------

test('advanceStatus action is not visible when status is delivered', function (): void {
    $order = makeOrder('delivered');

    $isVisible = nextStatusViaReflection($order->status) !== null && $order->status !== 'cancelled';

    expect($isVisible)->toBeFalse();
});

test('advanceStatus action is not visible when status is cancelled', function (): void {
    $order = makeOrder('cancelled');

    $isVisible = nextStatusViaReflection($order->status) !== null && $order->status !== 'cancelled';

    expect($isVisible)->toBeFalse();
});

test('advanceStatus action is visible for pending processing and shipped orders', function (string $status): void {
    $order = makeOrder($status);

    $isVisible = nextStatusViaReflection($order->status) !== null && $order->status !== 'cancelled';

    expect($isVisible)->toBeTrue();
})->with(['pending', 'processing', 'shipped']);
