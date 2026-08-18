<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\District;
use App\Models\Order;
use App\Models\Product;
use App\Models\Province;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function makeCheckoutCustomer(): Customer
{
    return Customer::query()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'phone' => '012345678',
    ]);
}

function makeActiveShippingMethodWithProvince(Province $province, float $fee = 3.00): ShippingMethod
{
    $method = ShippingMethod::factory()->create(['status' => 'active']);
    $method->provinces()->attach($province->id, ['fee' => $fee]);

    return $method;
}

function makeCheckoutProduct(int $stock = 5): Product
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
        'price' => 20,
        'stock_quantity' => $stock,
        'low_stock_threshold' => 2,
        'manage_stock' => true,
        'stock_status' => $stock > 0 ? 'in_stock' : 'out_of_stock',
        'is_active' => true,
        'is_featured' => false,
    ]);
}

// ---------------------------------------------------------------------------
// Province select only shows covered provinces
// ---------------------------------------------------------------------------

test('availableProvinces only returns provinces covered by at least one active shipping method', function (): void {
    $coveredProvince = Province::factory()->create(['is_active' => true]);
    $uncoveredProvince = Province::factory()->create(['is_active' => true]);

    $method = ShippingMethod::factory()->create(['status' => 'active']);
    $method->provinces()->attach($coveredProvince->id, ['fee' => 2.00]);

    $customer = makeCheckoutCustomer();
    $product = makeCheckoutProduct();

    session()->put('cart', [[
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => 20,
        'quantity' => 1,
    ]]);

    $component = Livewire::actingAs($customer, 'customer')
        ->test('pages::checkout')
        ->set('selectedShippingMethodId', $method->id);

    // Trigger a render and check the rendered HTML contains the covered province
    $component->assertSeeHtml($coveredProvince->name_en)
        ->assertDontSeeHtml($uncoveredProvince->name_en);
});

test('availableProvinces includes every active province when direct courier arrangement is available', function (): void {
    $coveredProvince = Province::factory()->create(['is_active' => true]);
    $uncoveredProvince = Province::factory()->create(['is_active' => true]);
    $method = ShippingMethod::factory()->create(['status' => 'active', 'requires_direct_arrangement' => true]);
    $customer = makeCheckoutCustomer();
    $product = makeCheckoutProduct();

    session()->put('cart', [[
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => 20,
        'quantity' => 1,
    ]]);

    Livewire::actingAs($customer, 'customer')
        ->test('pages::checkout')
        ->set('selectedShippingMethodId', $method->id)
        ->assertSeeHtml($coveredProvince->name_en)
        ->assertSeeHtml($uncoveredProvince->name_en);
});

// ---------------------------------------------------------------------------
// Province selection populates districts
// ---------------------------------------------------------------------------

test('selecting a province populates districts and clears any previous district', function (): void {
    $province = Province::factory()->create(['is_active' => true]);
    $district1 = District::factory()->create(['province_id' => $province->id, 'is_active' => true]);
    $district2 = District::factory()->create(['province_id' => $province->id, 'is_active' => true]);

    $method = makeActiveShippingMethodWithProvince($province);
    $customer = makeCheckoutCustomer();
    $product = makeCheckoutProduct();

    session()->put('cart', [[
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => 20,
        'quantity' => 1,
    ]]);

    $component = Livewire::actingAs($customer, 'customer')
        ->test('pages::checkout')
        ->set('districtId', $district1->id)   // Pre-set a district
        ->set('provinceId', $province->id);   // Changing province should reset district

    // districtId should be reset to null when province changes
    $component->assertSet('districtId', null);

    // Both districts should appear in the rendered output now province is selected
    $component->assertSeeHtml($district1->name_en)
        ->assertSeeHtml($district2->name_en);
});

// ---------------------------------------------------------------------------
// Shipping fee recalculation
// ---------------------------------------------------------------------------

test('selecting a province recalculates shipping fee from pivot', function (): void {
    $province = Province::factory()->create(['is_active' => true]);
    $method = makeActiveShippingMethodWithProvince($province, fee: 4.50);
    $customer = makeCheckoutCustomer();
    $product = makeCheckoutProduct();

    session()->put('cart', [[
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => 20,
        'quantity' => 1,
    ]]);

    $component = Livewire::actingAs($customer, 'customer')
        ->test('pages::checkout')
        ->set('selectedShippingMethodId', $method->id)
        ->set('provinceId', $province->id);

    expect($component->get('calculatedShippingFee'))->toBe(4.50);
});

test('checkout shows only the selected shipping method note and recalculates direct courier arrangement to free', function (): void {
    $province = Province::factory()->create(['is_active' => true]);
    $standardMethod = makeActiveShippingMethodWithProvince($province, fee: 4.50);
    $expressMethod = ShippingMethod::factory()->create([
        'name' => 'Express Delivery',
        'status' => 'active',
        'note' => 'Courier payment is arranged directly.',
        'requires_direct_arrangement' => true,
    ]);
    $customer = makeCheckoutCustomer();
    $product = makeCheckoutProduct();

    session()->put('cart', [[
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => 20,
        'quantity' => 1,
    ]]);

    $component = Livewire::actingAs($customer, 'customer')
        ->test('pages::checkout')
        ->set('provinceId', $province->id)
        ->set('selectedShippingMethodId', $expressMethod->id)
        ->assertSee('Courier payment is arranged directly.')
        ->assertSee('Free');

    expect($component->get('calculatedShippingFee'))->toBe(0.0);

    $component
        ->set('selectedShippingMethodId', $standardMethod->id)
        ->assertDontSee('Courier payment is arranged directly.');

    expect($component->get('calculatedShippingFee'))->toBe(4.50);
});

test('checkout renders no callout for a selected shipping method without a note', function (): void {
    $province = Province::factory()->create(['is_active' => true]);
    $method = makeActiveShippingMethodWithProvince($province);
    $customer = makeCheckoutCustomer();
    $product = makeCheckoutProduct();

    session()->put('cart', [[
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => 20,
        'quantity' => 1,
    ]]);

    Livewire::actingAs($customer, 'customer')
        ->test('pages::checkout')
        ->set('selectedShippingMethodId', $method->id)
        ->assertDontSee('data-flux-callout');
});

// ---------------------------------------------------------------------------
// Validation — district must belong to selected province
// ---------------------------------------------------------------------------

test('validation fails when district does not belong to the selected province', function (): void {
    $province1 = Province::factory()->create(['is_active' => true]);
    $province2 = Province::factory()->create(['is_active' => true]);
    $district2 = District::factory()->create(['province_id' => $province2->id, 'is_active' => true]);

    $method = makeActiveShippingMethodWithProvince($province1, fee: 2.00);
    $customer = makeCheckoutCustomer();
    $product = makeCheckoutProduct();

    session()->put('cart', [[
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => 20,
        'quantity' => 1,
    ]]);

    Livewire::actingAs($customer, 'customer')
        ->test('pages::checkout')
        ->set('useExistingAddress', false)
        ->set('selectedShippingMethodId', $method->id)
        ->set('full_name', 'Test User')
        ->set('phone', '012345678')
        ->set('address_line_1', '123 Street')
        ->set('provinceId', $province1->id)
        ->set('districtId', $district2->id)   // district from a DIFFERENT province
        ->call('nextStep')
        ->assertHasErrors(['districtId']);
});

// ---------------------------------------------------------------------------
// Order stores province_id and district_id
// ---------------------------------------------------------------------------

test('placing an order stores province_id and district_id on the order', function (): void {
    Mail::fake();

    $province = Province::factory()->create(['is_active' => true]);
    $district = District::factory()->create(['province_id' => $province->id, 'is_active' => true]);
    $method = makeActiveShippingMethodWithProvince($province, fee: 2.50);
    $customer = makeCheckoutCustomer();
    $product = makeCheckoutProduct(stock: 10);

    session()->put('cart', [[
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => 20,
        'quantity' => 1,
    ]]);

    Livewire::actingAs($customer, 'customer')
        ->test('pages::checkout')
        ->set('useExistingAddress', false)
        ->set('selectedShippingMethodId', $method->id)
        ->set('full_name', 'Test User')
        ->set('phone', '012345678')
        ->set('address_line_1', '123 Street')
        ->set('provinceId', $province->id)
        ->set('districtId', $district->id)
        ->set('paymentMethod', 'cash_on_delivery')
        ->call('placeOrder');

    $order = Order::query()->latest()->first();

    expect($order)->not->toBeNull()
        ->and($order->province_id)->toBe($province->id)
        ->and($order->district_id)->toBe($district->id)
        ->and((float) $order->shipping_cost)->toBe(2.50);
});

test('checkout fails when selected shipping method has no fee for the chosen province', function (): void {
    $province = Province::factory()->create(['is_active' => true]);
    $district = District::factory()->create(['province_id' => $province->id, 'is_active' => true]);
    $method = ShippingMethod::factory()->create(['status' => 'active']);
    $customer = makeCheckoutCustomer();
    $product = makeCheckoutProduct(stock: 10);

    session()->put('cart', [[
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => 20,
        'quantity' => 1,
    ]]);

    Livewire::actingAs($customer, 'customer')
        ->test('pages::checkout')
        ->set('useExistingAddress', false)
        ->set('selectedShippingMethodId', $method->id)
        ->set('full_name', 'Test User')
        ->set('phone', '012345678')
        ->set('address_line_1', '123 Street')
        ->set('provinceId', $province->id)
        ->set('districtId', $district->id)
        ->call('nextStep')
        ->assertHasErrors(['selectedShippingMethodId']);
});

test('checkout accepts a direct courier arrangement without a province fee and charges zero', function (): void {
    Mail::fake();

    $province = Province::factory()->create(['is_active' => true]);
    $district = District::factory()->create(['province_id' => $province->id, 'is_active' => true]);
    $method = ShippingMethod::factory()->create([
        'status' => 'active',
        'requires_direct_arrangement' => true,
    ]);
    $customer = makeCheckoutCustomer();
    $product = makeCheckoutProduct(stock: 10);

    session()->put('cart', [[
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => 20,
        'quantity' => 1,
    ]]);

    Livewire::actingAs($customer, 'customer')
        ->test('pages::checkout')
        ->set('useExistingAddress', false)
        ->set('selectedShippingMethodId', $method->id)
        ->set('full_name', 'Test User')
        ->set('phone', '012345678')
        ->set('address_line_1', '123 Street')
        ->set('provinceId', $province->id)
        ->set('districtId', $district->id)
        ->set('paymentMethod', 'cash_on_delivery')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->latest()->first();

    expect($method->provinces()->count())->toBe(0)
        ->and($order)->not->toBeNull()
        ->and((float) $order->shipping_cost)->toBe(0.0)
        ->and($order->province_id)->toBe($province->id)
        ->and($order->district_id)->toBe($district->id);
});
