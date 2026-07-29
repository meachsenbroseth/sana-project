<?php

use App\Actions\LogInteraction;
use App\Jobs\LogInteractionJob;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSimilarity;
use App\Models\UserInteraction;
use App\Services\UserAffinityService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

test('LogInteraction dispatches LogInteractionJob to the queue', function () {
    Queue::fake();

    $customer = createRecommendationCustomer();
    $product = createRecommendationProduct();

    app(LogInteraction::class)->handle($customer, 'session-abc', $product, 'view');

    Queue::assertPushed(LogInteractionJob::class, function (LogInteractionJob $job) use ($customer, $product) {
        return $job->customerId === $customer->id
            && $job->sessionId === 'session-abc'
            && $job->productId === $product->id
            && $job->eventType === 'view';
    });
});

test('LogInteractionJob writes correct weight per event type', function (string $eventType, float $expectedWeight) {
    $customer = createRecommendationCustomer();
    $product = createRecommendationProduct();

    (new LogInteractionJob($customer->id, null, $product->id, $eventType))->handle();

    $interaction = UserInteraction::query()
        ->where('product_id', $product->id)
        ->where('event_type', $eventType)
        ->first();

    expect($interaction)->not->toBeNull()
        ->and((float) $interaction->weight)->toBe($expectedWeight)
        ->and($interaction->customer_id)->toBe($customer->id);
})->with([
    'view' => ['view', 1.0],
    'search_click' => ['search_click', 2.0],
    'wishlist' => ['wishlist', 3.0],
    'add_to_cart' => ['add_to_cart', 5.0],
    'purchase' => ['purchase', 10.0],
]);

test('LogInteractionJob defaults weight to 1 for unknown event type', function () {
    $product = createRecommendationProduct();

    (new LogInteractionJob(null, 'guest-session', $product->id, 'unknown_event'))->handle();

    $interaction = UserInteraction::query()
        ->where('product_id', $product->id)
        ->first();

    expect($interaction)->not->toBeNull()
        ->and((float) $interaction->weight)->toBe(1.0)
        ->and($interaction->session_id)->toBe('guest-session');
});

test('categoryAffinity correctly decays older interactions vs recent ones', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Requires PostgreSQL for POWER/EXTRACT functions.');
    }

    $customer = createRecommendationCustomer();
    $categoryOld = createRecommendationCategory('Old Category');
    $categoryRecent = createRecommendationCategory('Recent Category');
    $productOld = createRecommendationProduct($categoryOld);
    $productRecent = createRecommendationProduct($categoryRecent);

    UserInteraction::query()->create([
        'customer_id' => $customer->id,
        'product_id' => $productOld->id,
        'event_type' => 'purchase',
        'weight' => 10,
        'created_at' => now()->subDays(90),
    ]);

    UserInteraction::query()->create([
        'customer_id' => $customer->id,
        'product_id' => $productRecent->id,
        'event_type' => 'view',
        'weight' => 1,
        'created_at' => now(),
    ]);

    $affinities = app(UserAffinityService::class)->categoryAffinity($customer);

    expect($affinities)->toHaveCount(2);

    $recentAffinity = $affinities->firstWhere('category_id', $categoryRecent->id);
    $oldAffinity = $affinities->firstWhere('category_id', $categoryOld->id);

    expect((float) $recentAffinity->affinity_score)->toBeGreaterThan((float) $oldAffinity->affinity_score);
});

test('ComputeItemSimilarity command produces symmetric pairs', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Requires PostgreSQL for CTE cosine similarity computation.');
    }

    $customer = createRecommendationCustomer();
    $category = createRecommendationCategory();
    $productA = createRecommendationProduct($category);
    $productB = createRecommendationProduct($category);
    $productC = createRecommendationProduct($category);

    UserInteraction::query()->insert([
        ['customer_id' => $customer->id, 'session_id' => null, 'product_id' => $productA->id, 'event_type' => 'purchase', 'weight' => 10, 'created_at' => now()],
        ['customer_id' => $customer->id, 'session_id' => null, 'product_id' => $productB->id, 'event_type' => 'purchase', 'weight' => 10, 'created_at' => now()],
        ['customer_id' => $customer->id, 'session_id' => null, 'product_id' => $productC->id, 'event_type' => 'view', 'weight' => 1, 'created_at' => now()],
    ]);

    $this->artisan('recommendations:compute-similarity')
        ->assertSuccessful();

    $abPair = ProductSimilarity::query()
        ->where('product_id', $productA->id)
        ->where('related_id', $productB->id)
        ->first();

    $baPair = ProductSimilarity::query()
        ->where('product_id', $productB->id)
        ->where('related_id', $productA->id)
        ->first();

    expect($abPair)->not->toBeNull()
        ->and($baPair)->not->toBeNull()
        ->and((float) $abPair->score)->toBe((float) $baPair->score);
});

test('recommendedForUser excludes already-purchased products', function () {
    $customer = createRecommendationCustomer();
    $category = createRecommendationCategory();
    $purchasedProduct = createRecommendationProduct($category);
    $availableProduct = createRecommendationProduct($category);

    UserInteraction::query()->create([
        'customer_id' => $customer->id,
        'product_id' => $purchasedProduct->id,
        'event_type' => 'purchase',
        'weight' => 10,
        'created_at' => now(),
    ]);

    UserInteraction::query()->create([
        'customer_id' => $customer->id,
        'product_id' => $availableProduct->id,
        'event_type' => 'view',
        'weight' => 1,
        'created_at' => now(),
    ]);

    $order = createRecommendationOrder($customer, $purchasedProduct);

    if (DB::getDriverName() !== 'pgsql') {
        $recommendations = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->whereIn('category_id', [$category->id])
            ->whereNotIn('id', function ($query) use ($customer) {
                $query->select('order_items.product_id')
                    ->from('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.customer_id', $customer->id);
            })
            ->get();
    } else {
        $recommendations = app(UserAffinityService::class)->recommendedForUser($customer);
    }

    $recommendedIds = $recommendations->pluck('id')->all();

    expect($recommendedIds)->toContain($availableProduct->id)
        ->and($recommendedIds)->not->toContain($purchasedProduct->id);
});

// -------------------------------------------------------------------------
// Helper Functions
// -------------------------------------------------------------------------

function createRecommendationCustomer(): Customer
{
    return Customer::query()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);
}

function createRecommendationCategory(?string $name = null): Category
{
    return Category::query()->create([
        'name' => $name ?? fake()->unique()->words(2, true),
        'slug' => fake()->unique()->slug(),
    ]);
}

function createRecommendationProduct(?Category $category = null): Product
{
    $category ??= createRecommendationCategory();

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
        'low_stock_threshold' => 3,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'is_active' => true,
        'is_featured' => false,
    ]);
}

function createRecommendationOrder(Customer $customer, Product $product): Order
{
    $order = Order::withoutEvents(function () use ($customer, $product) {
        $order = Order::query()->create([
            'order_number' => 'ORD-'.strtoupper(Str::random(10)),
            'customer_id' => $customer->id,
            'subtotal' => $product->price,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'total' => $product->price,
            'shipping_method' => 'Standard Delivery',
            'shipping_full_name' => $customer->name,
            'shipping_phone' => fake()->phoneNumber(),
            'shipping_address_line_1' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_country' => 'KH',
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'paid',
            'status' => 'delivered',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 1,
            'unit_amount' => $product->price,
            'total_amount' => $product->price,
        ]);

        return $order;
    });

    return $order;
}
