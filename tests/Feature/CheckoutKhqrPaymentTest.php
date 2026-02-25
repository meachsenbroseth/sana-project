<?php

use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('khqr success polling creates only one order and decrements stock once', function () {
    Mail::fake();

    $customer = Customer::query()->create([
        'name' => 'KHQR Customer',
        'email' => 'khqr-customer@example.com',
        'password' => 'password',
        'phone' => '012345678',
    ]);

    Address::query()->create([
        'customer_id' => $customer->id,
        'full_name' => $customer->name,
        'phone' => $customer->phone,
        'address_line_1' => 'Street 1',
        'city' => 'Phnom Penh',
        'state' => 'Phnom Penh',
        'country' => 'KH',
        'is_default' => true,
    ]);

    $shippingMethod = ShippingMethod::factory()->create([
        'name' => 'Standard Shipping',
        'cost' => 2,
        'status' => 'active',
    ]);

    $product = createCheckoutProductWithStock(10);

    session()->put('cart', [
        [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 50,
            'quantity' => 2,
        ],
    ]);

    $this->actingAs($customer, 'customer');

    \Mockery::mock('overload:KHQR\BakongKHQR')
        ->shouldReceive('checkTransactionByMD5')
        ->once()
        ->with('khqr-md5-123')
        ->andReturn(['responseCode' => 0]);

    Livewire::test('pages::checkout')
        ->set('selectedShippingMethodId', $shippingMethod->id)
        ->set('khqrMd5', 'khqr-md5-123')
        ->set('paymentStartedAtTs', now()->timestamp)
        ->set('showKhqrModal', true)
        ->call('checkKhqrStatus')
        ->call('checkKhqrStatus');

    expect(Order::query()->where('transaction_id', 'khqr-md5-123')->count())->toBe(1)
        ->and($product->fresh()->stock_quantity)->toBe(8)
        ->and($product->fresh()->stock_status)->toBe('in_stock');
});

test('khqr polling returns early when order is already processing', function () {
    Mail::fake();

    $customer = Customer::query()->create([
        'name' => 'Polling Customer',
        'email' => 'khqr-polling@example.com',
        'password' => 'password',
        'phone' => '098765432',
    ]);

    ShippingMethod::factory()->create([
        'status' => 'active',
    ]);

    session()->put('cart', [
        [
            'product_id' => 999,
            'name' => 'Unreachable Product',
            'price' => 10,
            'quantity' => 1,
        ],
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::checkout')
        ->set('khqrMd5', 'khqr-lock-md5')
        ->set('paymentStartedAtTs', now()->timestamp)
        ->set('orderProcessing', true)
        ->call('checkKhqrStatus');

    expect(Order::query()->count())->toBe(0);
});

function createCheckoutProductWithStock(int $stockQuantity): Product
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
        'price' => 50,
        'stock_quantity' => $stockQuantity,
        'low_stock_threshold' => 2,
        'manage_stock' => true,
        'stock_status' => $stockQuantity > 0 ? 'in_stock' : 'out_of_stock',
        'is_active' => true,
        'is_featured' => false,
    ]);
}
