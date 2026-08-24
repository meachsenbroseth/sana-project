<?php

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function (): void {
    Http::fake();
    Mail::fake();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Creates a minimal order for badge-click testing.
 */
function makeOrderForBadge(string $status = 'pending'): Order
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
        'payment_status' => 'pending',
        'status' => $status,
    ]);
}

/**
 * Invokes nextStatus() via reflection so tests stay black-box.
 */
function nextStatusForBadge(string $current): ?string
{
    $ref = new ReflectionClass(OrdersTable::class);
    $method = $ref->getMethod('nextStatus');
    $method->setAccessible(true);

    return $method->invoke(null, $current);
}

// ---------------------------------------------------------------------------
// Clicking the status badge advances a pending order to processing
// ---------------------------------------------------------------------------

test('clicking the status badge advances a pending order to processing', function (): void {
    $user = User::factory()->create();
    $order = makeOrderForBadge('pending');

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(ListOrders::class)
        ->callTableAction('advanceStatusBadge', $order)
        ->assertSuccessful();

    expect($order->fresh()->status)->toBe('processing');
});

// ---------------------------------------------------------------------------
// Repeated badge clicks walk the full pipeline one step at a time
// ---------------------------------------------------------------------------

test('repeated badge clicks walk the order through the full pipeline', function (): void {
    $user = User::factory()->create();
    $order = makeOrderForBadge('pending');

    $this->actingAs($user);
    Gate::before(fn () => true);

    // pending → processing
    Livewire::test(ListOrders::class)
        ->callTableAction('advanceStatusBadge', $order);
    expect($order->fresh()->status)->toBe('processing');

    // processing → shipped
    Livewire::test(ListOrders::class)
        ->callTableAction('advanceStatusBadge', $order->fresh());
    expect($order->fresh()->status)->toBe('shipped');

    // shipped → delivered
    Livewire::test(ListOrders::class)
        ->callTableAction('advanceStatusBadge', $order->fresh());
    expect($order->fresh()->status)->toBe('delivered');
});

// ---------------------------------------------------------------------------
// Badge action is not visible for terminal statuses
// ---------------------------------------------------------------------------

test('the status badge action is not visible for a delivered order', function (): void {
    $order = makeOrderForBadge('delivered');

    $isVisible = nextStatusForBadge($order->status) !== null && $order->status !== 'cancelled';

    expect($isVisible)->toBeFalse();
});

test('the status badge action is not visible for a cancelled order', function (): void {
    $order = makeOrderForBadge('cancelled');

    $isVisible = nextStatusForBadge($order->status) !== null && $order->status !== 'cancelled';

    expect($isVisible)->toBeFalse();
});

// ---------------------------------------------------------------------------
// Regression: the removed advanceStatus row Action is no longer registered
// ---------------------------------------------------------------------------

test('the advanceStatus row action no longer exists in the action group', function (): void {
    $user = User::factory()->create();
    $order = makeOrderForBadge('pending');

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(ListOrders::class)
        ->assertTableActionDoesNotExist('advanceStatus', null, $order)
        ->assertSuccessful();
});
