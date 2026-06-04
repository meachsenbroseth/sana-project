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

test('stock decreases when payment status becomes paid', function () {
    Mail::fake();
    Http::fake();

    $product = createProductWithStock(10);
    $order = createOrderWithItem($product, 3);

    $order->update(['payment_status' => 'paid']);

    expect($product->fresh()->stock_quantity)->toBe(7)
        ->and($product->fresh()->stock_status)->toBe('in_stock')
        ->and($order->fresh()->stock_deducted_at)->not->toBeNull();
});

test('stock does not decrease twice', function () {
    Mail::fake();
    Http::fake();

    $product = createProductWithStock(10);
    $order = createOrderWithItem($product, 4);

    $order->update(['payment_status' => 'paid']);
    $firstDeductedAt = $order->fresh()->stock_deducted_at;

    $order->update(['status' => 'processing']);
    $order->update(['admin_notes' => 'Saved again']);

    expect($product->fresh()->stock_quantity)->toBe(6)
        ->and($product->fresh()->stock_status)->toBe('in_stock')
        ->and($order->fresh()->stock_deducted_at->equalTo($firstDeductedAt))->toBeTrue();
});

test('unmanaged stock product is ignored', function () {
    Mail::fake();
    Http::fake();

    $product = createProductWithStock(10, false);
    $order = createOrderWithItem($product, 4);

    $order->update(['payment_status' => 'paid']);

    expect($product->fresh()->stock_quantity)->toBe(10)
        ->and($product->fresh()->stock_status)->toBe('in_stock')
        ->and($order->fresh()->stock_deducted_at)->not->toBeNull();
});

test('product becomes out of stock when quantity reaches zero', function () {
    Mail::fake();
    Http::fake();

    $product = createProductWithStock(4);
    $order = createOrderWithItem($product, 4);

    $order->update(['payment_status' => 'paid']);

    expect($product->fresh()->stock_quantity)->toBe(0)
        ->and($product->fresh()->stock_status)->toBe('out_of_stock')
        ->and($order->fresh()->stock_deducted_at)->not->toBeNull();
});

test('insufficient stock does not corrupt data', function () {
    Mail::fake();
    Http::fake();

    $product = createProductWithStock(2);
    $order = createOrderWithItem($product, 5);

    expect(fn () => $order->update(['payment_status' => 'paid']))
        ->toThrow(ValidationException::class);

    expect($product->fresh()->stock_quantity)->toBe(2)
        ->and($product->fresh()->stock_status)->toBe('in_stock')
        ->and($order->fresh()->stock_deducted_at)->toBeNull();
});

function createOrderWithItem(Product $product, int $quantity): Order
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

function createProductWithStock(int $stockQuantity, bool $manageStock = true): Product
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
        'manage_stock' => $manageStock,
        'stock_status' => $stockQuantity > 0 ? 'in_stock' : 'out_of_stock',
        'is_active' => true,
        'is_featured' => false,
    ]);
}
