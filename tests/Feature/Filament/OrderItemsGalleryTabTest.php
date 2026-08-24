<?php

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
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
 * @return array{order: Order, customer: Customer}
 */
function makeOrderForGallery(): array
{
    $customer = Customer::query()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);

    $order = Order::query()->create([
        'customer_id' => $customer->id,
        'subtotal' => 200,
        'discount_amount' => 0,
        'shipping_cost' => 10,
        'total' => 210,
        'shipping_method' => 'Standard Delivery',
        'shipping_full_name' => $customer->name,
        'shipping_phone' => fake()->phoneNumber(),
        'shipping_address_line_1' => fake()->streetAddress(),
        'shipping_city' => fake()->city(),
        'shipping_country' => 'KH',
        'payment_method' => 'cash_on_delivery',
        'payment_status' => 'pending',
        'status' => 'pending',
    ]);

    return ['order' => $order, 'customer' => $customer];
}

/**
 * Creates a Product with a primary ProductImage.
 */
function makeProductWithImage(): Product
{
    $category = Category::query()->create([
        'name' => fake()->unique()->words(2, true),
        'slug' => fake()->unique()->slug(),
    ]);

    $brand = Brand::query()->create([
        'name' => fake()->unique()->company(),
        'slug' => fake()->unique()->slug(),
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => fake()->unique()->words(3, true),
        'slug' => fake()->unique()->slug(),
        'sku' => fake()->unique()->bothify('SKU-########'),
        'price' => 99.99,
        'stock_quantity' => 50,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'is_active' => true,
        'is_featured' => false,
    ]);

    ProductImage::query()->create([
        'product_id' => $product->id,
        'image_path' => 'products/test-image.jpg',
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    return $product;
}

/**
 * Creates an OrderItem attached to an order, with snapshotted fields.
 */
function makeOrderItemForGallery(Order $order, ?int $productId, string $productName = 'Test Product', string $sku = 'SKU-TEST'): OrderItem
{
    return OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $productId,
        'product_name' => $productName,
        'product_sku' => $sku,
        'quantity' => 2,
        'unit_amount' => 99.99,
        'total_amount' => 199.98,
    ]);
}

// ---------------------------------------------------------------------------
// Test 1: Gallery tab renders without error
// ---------------------------------------------------------------------------

test('edit order page renders with the items gallery tab without error', function (): void {
    $user = User::factory()->create();
    ['order' => $order] = makeOrderForGallery();

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->assertSuccessful();
});

// ---------------------------------------------------------------------------
// Test 2: Image src rendered for item with a primary product image
// ---------------------------------------------------------------------------

test('gallery tab renders the product image url for an order item with a primary image', function (): void {
    $user = User::factory()->create();
    ['order' => $order] = makeOrderForGallery();
    $product = makeProductWithImage();
    makeOrderItemForGallery($order, $product->id, $product->name, $product->sku);

    $this->actingAs($user);
    Gate::before(fn () => true);

    // The Placeholder renders an <img> containing the image path via asset()
    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('products/test-image.jpg');
});

// ---------------------------------------------------------------------------
// Test 3: Soft-deleted product does not break the gallery tab
// ---------------------------------------------------------------------------

test('gallery tab renders gracefully when the linked product has been soft deleted', function (): void {
    $user = User::factory()->create();
    ['order' => $order] = makeOrderForGallery();

    // Create a product, attach an order item with snapshotted fields, then soft-delete the product.
    // The gallery Placeholder resolves $record->product via the BelongsTo relationship, which
    // excludes soft-deleted models by default — so $record->product will be null, triggering
    // the SVG placeholder fallback. The snapshotted product_name / product_sku on the OrderItem
    // remain intact (soft-delete only sets products.deleted_at; order_items row is untouched).
    $product = makeProductWithImage();
    $item = makeOrderItemForGallery($order, $product->id, 'Deleted Product Snapshot', 'SKU-DELETED');
    $product->delete(); // soft-delete

    // Verify the snapshotted data survives the soft-delete at the model level.
    $freshItem = $item->fresh();
    expect($freshItem->product_name)->toBe('Deleted Product Snapshot');
    expect($freshItem->product_sku)->toBe('SKU-DELETED');
    expect($freshItem->product)->toBeNull(); // soft-deleted product not resolved

    $this->actingAs($user);
    Gate::before(fn () => true);

    // The edit page must render without throwing any exception.
    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->assertSuccessful();
});

// ---------------------------------------------------------------------------
// Test 4: Regression — Tab 1 plain Repeater still works unchanged
// ---------------------------------------------------------------------------

test('tab 1 plain repeater still renders the order item sku without regression', function (): void {
    $user = User::factory()->create();
    ['order' => $order] = makeOrderForGallery();
    $product = makeProductWithImage();
    makeOrderItemForGallery($order, $product->id, $product->name, 'SKU-REGRESSION');

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('SKU-REGRESSION');
});
