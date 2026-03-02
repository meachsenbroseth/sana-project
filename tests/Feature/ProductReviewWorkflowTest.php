<?php

use App\Livewire\ProductReviewForm;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;

test('customer can submit one pending review after delivered purchase', function () {
    $customer = createCustomer();
    $product = createProduct();
    $order = createOrder($customer, 'delivered');

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 1,
        'unit_amount' => $product->price,
        'total_amount' => $product->price,
    ]);

    $this->actingAs($customer, 'customer');

    $component = app(ProductReviewForm::class);
    $component->mount($product);
    $component->rating = 4;
    $component->title = 'Solid performance';
    $component->comment = 'Works great for daily tasks.';
    $component->submitReview();

    $review = Review::query()->where('product_id', $product->id)->where('customer_id', $customer->id)->first();

    expect($review)->not()->toBeNull()
        ->and($review->is_approved)->toBeFalse()
        ->and($review->is_verified_purchase)->toBeTrue()
        ->and($review->order_id)->toBe($order->id)
        ->and(session('review_success'))->toBe('Review submitted and awaiting approval');
});

test('customer cannot submit review without delivered purchase', function () {
    $customer = createCustomer();
    $product = createProduct();
    $order = createOrder($customer, 'processing');

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 1,
        'unit_amount' => $product->price,
        'total_amount' => $product->price,
    ]);

    $this->actingAs($customer, 'customer');

    $component = app(ProductReviewForm::class);
    $component->mount($product);
    $component->rating = 5;
    $component->title = 'Blocked review';
    $component->comment = 'Should not be allowed';
    $component->submitReview();

    expect(Review::query()->where('product_id', $product->id)->count())->toBe(0)
        ->and($component->getErrorBag()->has('review'))->toBeTrue();
});

test('customer cannot submit duplicate review for the same product', function () {
    $customer = createCustomer();
    $product = createProduct();

    Review::query()->create([
        'product_id' => $product->id,
        'customer_id' => $customer->id,
        'rating' => 5,
        'title' => 'First review',
        'comment' => 'Already reviewed',
        'is_verified_purchase' => true,
        'is_approved' => false,
    ]);

    $this->actingAs($customer, 'customer');

    $component = app(ProductReviewForm::class);
    $component->mount($product);
    $component->rating = 4;
    $component->title = 'Second review';
    $component->comment = 'Should fail';
    $component->submitReview();

    expect(Review::query()->where('product_id', $product->id)->where('customer_id', $customer->id)->count())->toBe(1)
        ->and($component->getErrorBag()->has('review'))->toBeTrue();
});

test('product ratings and review counts use approved reviews only', function () {
    $product = createProduct();
    $firstCustomer = createCustomer();
    $secondCustomer = createCustomer();
    $thirdCustomer = createCustomer();

    Review::query()->create([
        'product_id' => $product->id,
        'customer_id' => $firstCustomer->id,
        'rating' => 5,
        'title' => 'Top tier',
        'comment' => 'Excellent',
        'is_verified_purchase' => true,
        'is_approved' => true,
    ]);

    Review::query()->create([
        'product_id' => $product->id,
        'customer_id' => $secondCustomer->id,
        'rating' => 4,
        'title' => 'Great',
        'comment' => 'Very good',
        'is_verified_purchase' => true,
        'is_approved' => true,
    ]);

    Review::query()->create([
        'product_id' => $product->id,
        'customer_id' => $thirdCustomer->id,
        'rating' => 1,
        'title' => 'Pending',
        'comment' => 'Pending approval',
        'is_verified_purchase' => false,
        'is_approved' => false,
    ]);

    $freshProduct = Product::query()->findOrFail($product->id);

    expect((float) $freshProduct->average_rating)->toBe(4.5)
        ->and($freshProduct->reviews_count)->toBe(2);
});

function createProduct(): Product
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
        'stock_quantity' => 10,
        'low_stock_threshold' => 2,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'status' => 'new',
        'is_active' => true,
        'is_featured' => false,
    ]);
}

function createCustomer(): Customer
{
    return Customer::query()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'phone' => fake()->phoneNumber(),
    ]);
}

function createOrder(Customer $customer, string $status): Order
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
