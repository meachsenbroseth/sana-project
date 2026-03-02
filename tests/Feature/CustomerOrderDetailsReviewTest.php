<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\QueryException;

test('review lookup for customer product order blocks duplicates for the same order only', function () {
    $customer = createCustomerForOrderReview();
    $product = createProductForOrderReview();
    $order = createOrderForOrderReview($customer, 'delivered');

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 1,
        'unit_amount' => $product->price,
        'total_amount' => $product->price,
    ]);

    Review::query()->create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'order_id' => $order->id,
        'rating' => 5,
        'comment' => 'Already reviewed',
        'is_verified_purchase' => true,
        'is_approved' => false,
    ]);

    $alreadyReviewed = Review::query()
        ->where('customer_id', $customer->id)
        ->where('order_id', $order->id)
        ->where('product_id', $product->id)
        ->exists();

    expect($alreadyReviewed)->toBeTrue();
});

test('a customer can review the same product in different orders but not twice in the same order', function () {
    $customer = createCustomerForOrderReview();
    $product = createProductForOrderReview();
    $firstOrder = createOrderForOrderReview($customer, 'delivered');
    $secondOrder = createOrderForOrderReview($customer, 'delivered');

    $firstReview = Review::query()->create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'order_id' => $firstOrder->id,
        'rating' => 5,
        'comment' => 'First order review',
        'is_verified_purchase' => true,
        'is_approved' => false,
    ]);

    expect($firstReview)->not->toBeNull();

    $secondReview = Review::query()->create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'order_id' => $secondOrder->id,
        'rating' => 4,
        'comment' => 'Second order review',
        'is_verified_purchase' => true,
        'is_approved' => false,
    ]);

    expect($secondReview)->not->toBeNull();

    expect(fn () => Review::query()->create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'order_id' => $secondOrder->id,
        'rating' => 3,
        'comment' => 'Duplicate in same order',
        'is_verified_purchase' => true,
        'is_approved' => false,
    ]))->toThrow(QueryException::class);
});

function createCustomerForOrderReview(): Customer
{
    return Customer::query()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'phone' => fake()->phoneNumber(),
    ]);
}

function createProductForOrderReview(): Product
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
        'stock_quantity' => 20,
        'low_stock_threshold' => 2,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'status' => 'new',
        'is_active' => true,
        'is_featured' => false,
    ]);
}

function createOrderForOrderReview(Customer $customer, string $status): Order
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
