<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;

test('it deducts stock and updates stock status when order becomes processing', function () {
    Mail::fake();

    $product = createProductWithStock(10);
    $order = createOrderWithItem($product, 3);

    $order->update(['status' => 'processing']);

    expect($product->fresh()->stock_quantity)->toBe(7)
        ->and($product->fresh()->stock_status)->toBe('in_stock');
});

test('it never allows stock to go negative when order becomes processing', function () {
    Mail::fake();

    $product = createProductWithStock(2);
    $order = createOrderWithItem($product, 5);

    $order->update(['status' => 'processing']);

    expect($product->fresh()->stock_quantity)->toBe(0)
        ->and($product->fresh()->stock_status)->toBe('out_of_stock');
});

test('it does not deduct stock again when status changes from processing to completed', function () {
    Mail::fake();

    $product = createProductWithStock(10);
    $order = createOrderWithItem($product, 4);

    $order->update(['status' => 'processing']);
    $order->update(['status' => 'completed']);

    expect($product->fresh()->stock_quantity)->toBe(6)
        ->and($product->fresh()->stock_status)->toBe('in_stock');
});

test('it only deducts stock once even if order returns to processing again', function () {
    Mail::fake();

    $product = createProductWithStock(10);
    $order = createOrderWithItem($product, 4);

    $order->update(['status' => 'processing']);
    $order->update(['status' => 'pending']);
    $order->update(['status' => 'processing']);

    expect($product->fresh()->stock_quantity)->toBe(6)
        ->and($product->fresh()->stock_status)->toBe('in_stock');
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

function createProductWithStock(int $stockQuantity): Product
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
        'stock_status' => $stockQuantity > 0 ? 'in_stock' : 'out_of_stock',
        'is_active' => true,
        'is_featured' => false,
    ]);
}
