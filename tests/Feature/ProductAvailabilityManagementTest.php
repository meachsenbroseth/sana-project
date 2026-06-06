<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Http::fake();
    Mail::fake();
});

test('product stock status is synchronized when a product is created', function () {
    $product = createProductForAvailabilityManagement(0, 'in_stock');

    expect($product->refresh()->stock_status)->toBe('out_of_stock');
});

test('product stock status is synchronized when stock is restocked by an admin', function () {
    $product = createProductForAvailabilityManagement(0);

    $product->update(['stock_quantity' => 5]);

    expect($product->refresh()->stock_status)->toBe('in_stock')
        ->and($product->isAvailableForPurchase())->toBeTrue();
});

test('order stock deduction synchronizes product availability', function () {
    $product = createProductForAvailabilityManagement(2);
    $order = createOrderForAvailabilityManagement($product, 2);

    $order->update(['payment_status' => 'paid']);

    expect($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->stock_status)->toBe('out_of_stock')
        ->and($product->isAvailableForPurchase())->toBeFalse();
});

test('paid orders cannot deduct stock from unavailable products', function () {
    $product = createProductForAvailabilityManagement(0);
    $order = createOrderForAvailabilityManagement($product, 1);

    expect(fn () => $order->update(['payment_status' => 'paid']))
        ->toThrow(ValidationException::class);

    expect($product->refresh()->stock_quantity)->toBe(0)
        ->and($product->stock_status)->toBe('out_of_stock')
        ->and($order->refresh()->stock_deducted_at)->toBeNull();
});

test('product details show out of stock badge and hide purchase controls', function () {
    $product = createProductForAvailabilityManagement(0);

    $this
        ->get(route('products.show', $product->slug))
        ->assertSuccessful()
        ->assertSeeText('Out of Stock')
        ->assertDontSeeText('Add to Cart');
});

function createProductForAvailabilityManagement(int $stockQuantity, string $stockStatus = 'in_stock'): Product
{
    $category = Category::query()->create([
        'name' => fake()->unique()->words(2, true),
        'slug' => fake()->unique()->slug(),
    ]);

    $brand = Brand::query()->create([
        'name' => fake()->unique()->company(),
        'slug' => fake()->unique()->slug(),
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => fake()->unique()->words(3, true),
        'slug' => fake()->unique()->slug(),
        'sku' => fake()->unique()->bothify('SKU-########'),
        'price' => 100,
        'stock_quantity' => $stockQuantity,
        'low_stock_threshold' => 3,
        'manage_stock' => true,
        'stock_status' => $stockStatus,
        'status' => 'new',
        'is_active' => true,
        'is_featured' => false,
    ]);
}

function createOrderForAvailabilityManagement(Product $product, int $quantity): Order
{
    $customer = Customer::query()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);

    $order = Order::query()->create([
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
        'shipping_country' => 'US',
        'payment_method' => 'cash_on_delivery',
        'payment_status' => 'pending',
        'status' => 'pending',
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => $quantity,
        'unit_amount' => $product->price,
        'total_amount' => $product->price * $quantity,
    ]);

    return $order;
}
