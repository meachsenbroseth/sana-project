<?php

use App\Models\Customer;
use App\Models\ShippingMethod;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('homepage loads banner image dynamically from site settings', function () {
    SiteSetting::factory()->create([
        'banner_image' => 'banners/homepage-banner.jpg',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('homepage-banner.jpg');
});

test('homepage exposes multiple banners for slider when configured', function () {
    SiteSetting::factory()->create([
        'banner_images' => [
            [
                'image' => 'banners/homepage-banner-2.jpg',
                'title' => 'Second',
                'link' => null,
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'image' => 'banners/homepage-banner-1.jpg',
                'title' => 'First',
                'link' => null,
                'status' => 'active',
                'sort_order' => 1,
            ],
        ],
        'banner_image' => 'banners/homepage-banner-1.jpg',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSeeInOrder([
        'homepage-banner-1.jpg',
        'homepage-banner-2.jpg',
    ]);
});

test('homepage slider only includes active banners', function () {
    SiteSetting::factory()->create([
        'banner_images' => [
            [
                'image' => 'banners/homepage-banner-active.jpg',
                'title' => 'Active Banner',
                'link' => 'https://example.com/active',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'image' => 'banners/homepage-banner-inactive.jpg',
                'title' => 'Inactive Banner',
                'link' => 'https://example.com/inactive',
                'status' => 'inactive',
                'sort_order' => 2,
            ],
        ],
        'banner_image' => 'banners/homepage-banner-active.jpg',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('homepage-banner-active.jpg');
    $response->assertDontSee('homepage-banner-inactive.jpg');
});

test('checkout loads only active shipping methods', function () {
    $customer = Customer::query()->create([
        'name' => 'Checkout Customer',
        'email' => 'checkout@example.com',
        'password' => Hash::make('password'),
    ]);

    ShippingMethod::factory()->create([
        'name' => 'Express Delivery',
        'status' => 'active',
    ]);

    ShippingMethod::factory()->inactive()->create([
        'name' => 'Hidden Delivery',
    ]);

    $category = \App\Models\Category::query()->create(['name' => 'Cat1', 'slug' => 'cat1']);
    $brand = \App\Models\Brand::query()->create(['name' => 'Brand1', 'slug' => 'brand1']);
    $product = \App\Models\Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Test Product',
        'slug' => 'test-product',
        'sku' => 'SKU-1',
        'price' => 25,
        'stock_quantity' => 10,
        'stock_status' => 'in_stock',
        'is_active' => true,
    ]);

    session()->put('cart', [
        [
            'product_id' => $product->id,
            'name' => 'Test Product',
            'price' => 25,
            'quantity' => 1,
        ],
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::checkout')
        ->assertSee('Express Delivery')
        ->assertDontSee('Hidden Delivery');
});

test('checkout requires selecting an active shipping method before moving to review', function () {
    $customer = Customer::query()->create([
        'name' => 'Shipping Customer',
        'email' => 'shipping@example.com',
        'password' => Hash::make('password'),
    ]);

    $province = \App\Models\Province::factory()->create();
    $district = \App\Models\District::factory()->create(['province_id' => $province->id]);
    
    $activeShippingMethod = ShippingMethod::factory()->create([
        'name' => 'Standard',
        'status' => 'active',
    ]);
    $activeShippingMethod->provinces()->attach($province->id, ['fee' => 5]);

    $inactiveShippingMethod = ShippingMethod::factory()->inactive()->create([
        'name' => 'Disabled Shipping',
    ]);

    $category = \App\Models\Category::query()->create(['name' => 'Cat2', 'slug' => 'cat2']);
    $brand = \App\Models\Brand::query()->create(['name' => 'Brand2', 'slug' => 'brand2']);
    $product = \App\Models\Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Another Product',
        'slug' => 'another-product',
        'sku' => 'SKU-2',
        'price' => 40,
        'stock_quantity' => 10,
        'stock_status' => 'in_stock',
        'is_active' => true,
    ]);

    session()->put('cart', [
        [
            'product_id' => $product->id,
            'name' => 'Another Product',
            'price' => 40,
            'quantity' => 1,
        ],
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::checkout')
        ->set('useExistingAddress', false)
        ->set('full_name', 'Shipping Customer')
        ->set('phone', '012345678')
        ->set('address_line_1', 'Street 1')
        ->set('provinceId', $province->id)
        ->set('districtId', $district->id)
        ->set('country', 'KH')
        ->set('selectedShippingMethodId', $inactiveShippingMethod->id)
        ->call('nextStep')
        ->assertSet('step', 1)
        ->set('selectedShippingMethodId', $activeShippingMethod->id)
        ->call('nextStep')
        ->assertSet('step', 2);
});
