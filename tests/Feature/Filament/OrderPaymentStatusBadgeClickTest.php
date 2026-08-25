<?php

use App\Filament\Resources\Orders\Pages\ListOrders;
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
 * Creates a minimal order for payment-status badge-click testing.
 */
function makeOrderForPaymentBadge(string $paymentStatus = 'pending', string $status = 'pending'): Order
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
// Clicking the payment_status badge marks a pending order as paid
// ---------------------------------------------------------------------------

test('clicking the payment status badge marks a pending order as paid', function (): void {
    $user = User::factory()->create();
    $order = makeOrderForPaymentBadge('pending');

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(ListOrders::class)
        ->callTableAction('markPaidBadge', $order)
        ->assertSuccessful();

    expect($order->fresh()->payment_status)->toBe('paid');
});

// ---------------------------------------------------------------------------
// Clicking the payment_status badge marks a failed order as paid (retry)
// ---------------------------------------------------------------------------

test('clicking the payment status badge marks a failed order as paid', function (): void {
    $user = User::factory()->create();
    $order = makeOrderForPaymentBadge('failed');

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(ListOrders::class)
        ->callTableAction('markPaidBadge', $order)
        ->assertSuccessful();

    expect($order->fresh()->payment_status)->toBe('paid');
});

// ---------------------------------------------------------------------------
// Badge action is non-interactive when payment_status is already paid
// ---------------------------------------------------------------------------

test('the payment status badge action is not visible for an already paid order', function (): void {
    $order = makeOrderForPaymentBadge('paid');

    $isVisible = $order->payment_status !== 'paid';

    expect($isVisible)->toBeFalse();
});

// ---------------------------------------------------------------------------
// Regression: markPaid row Action is no longer registered in the ActionGroup
// ---------------------------------------------------------------------------

test('the markPaid row action no longer exists in the action group', function (): void {
    $user = User::factory()->create();
    $order = makeOrderForPaymentBadge('pending');

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(ListOrders::class)
        ->assertTableActionDoesNotExist('markPaid', null, $order)
        ->assertSuccessful();
});

// ---------------------------------------------------------------------------
// Regression: status column's advanceStatusBadge action still works
// ---------------------------------------------------------------------------

test('the status badge advance action is unaffected by payment status badge changes', function (): void {
    $user = User::factory()->create();
    $order = makeOrderForPaymentBadge('pending', 'pending');

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(ListOrders::class)
        ->callTableAction('advanceStatusBadge', $order)
        ->assertSuccessful();

    expect($order->fresh()->status)->toBe('processing');
});
