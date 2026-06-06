<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Http::fake();
    Mail::fake();
});

test('a customer can confirm delivery for their shipped order', function () {
    $customer = createCustomerForDeliveryConfirmation();
    $order = createOrderForDeliveryConfirmation($customer, 'shipped');

    $response = $this
        ->actingAs($customer, 'customer')
        ->post(route('customer.orders.confirm-delivery', $order));

    $response
        ->assertRedirect()
        ->assertSessionHas('delivery_confirmed', 'Delivery confirmed successfully.');

    expect($order->refresh()->status)->toBe('delivered');

    $history = OrderStatusHistory::query()
        ->where('order_id', $order->id)
        ->where('status', 'delivered')
        ->latest()
        ->first();

    expect($history?->notes)->toBe('Delivery confirmed by customer.');
});

test('a customer cannot confirm delivery for another customer order', function () {
    $customer = createCustomerForDeliveryConfirmation();
    $otherCustomer = createCustomerForDeliveryConfirmation();
    $order = createOrderForDeliveryConfirmation($otherCustomer, 'shipped');

    $this
        ->actingAs($customer, 'customer')
        ->post(route('customer.orders.confirm-delivery', $order))
        ->assertForbidden();

    expect($order->refresh()->status)->toBe('shipped');
});

test('delivery confirmation only accepts shipped orders', function (string $status, string $message) {
    $customer = createCustomerForDeliveryConfirmation();
    $order = createOrderForDeliveryConfirmation($customer, $status);

    $this
        ->actingAs($customer, 'customer')
        ->from(route('customer.orders.show', $order->id))
        ->post(route('customer.orders.confirm-delivery', $order))
        ->assertRedirect(route('customer.orders.show', $order->id))
        ->assertSessionHasErrors(['order' => $message]);

    expect($order->refresh()->status)->toBe($status);
})->with([
    'pending' => ['pending', 'Only shipped orders can be marked as delivered.'],
    'processing' => ['processing', 'Only shipped orders can be marked as delivered.'],
    'delivered' => ['delivered', 'This order has already been marked as delivered.'],
    'cancelled' => ['cancelled', 'Cancelled orders cannot be marked as delivered.'],
]);

test('the confirm delivery button is only shown for shipped orders', function () {
    $customer = createCustomerForDeliveryConfirmation();
    $shippedOrder = createOrderForDeliveryConfirmation($customer, 'shipped');
    $processingOrder = createOrderForDeliveryConfirmation($customer, 'processing');

    $this
        ->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $shippedOrder->id))
        ->assertSuccessful()
        ->assertSee('Confirm Delivery');

    $this
        ->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $processingOrder->id))
        ->assertSuccessful()
        ->assertDontSee('Confirm Delivery');
});

function createCustomerForDeliveryConfirmation(): Customer
{
    return Customer::query()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'phone' => fake()->phoneNumber(),
    ]);
}

function createOrderForDeliveryConfirmation(Customer $customer, string $status): Order
{
    return Order::query()->create([
        'customer_id' => $customer->id,
        'subtotal' => 100,
        'discount_amount' => 0,
        'shipping_cost' => 0,
        'total' => 100,
        'shipping_method' => 'Standard Delivery',
        'shipping_full_name' => $customer->name,
        'shipping_phone' => $customer->phone ?? fake()->phoneNumber(),
        'shipping_address_line_1' => fake()->streetAddress(),
        'shipping_city' => fake()->city(),
        'shipping_country' => 'KH',
        'payment_method' => 'cash_on_delivery',
        'payment_status' => 'paid',
        'status' => $status,
    ]);
}
