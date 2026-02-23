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

    session()->put('cart', [
        [
            'product_id' => 1,
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

    $activeShippingMethod = ShippingMethod::factory()->create([
        'name' => 'Standard Shipping',
        'cost' => 5.00,
        'status' => 'active',
    ]);

    $inactiveShippingMethod = ShippingMethod::factory()->inactive()->create([
        'name' => 'Disabled Shipping',
        'cost' => 12.00,
    ]);

    session()->put('cart', [
        [
            'product_id' => 2,
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
        ->set('city', 'Phnom Penh')
        ->set('country', 'KH')
        ->set('selectedShippingMethodId', $inactiveShippingMethod->id)
        ->call('nextStep')
        ->assertSet('step', 1)
        ->set('selectedShippingMethodId', $activeShippingMethod->id)
        ->call('nextStep')
        ->assertSet('step', 2);
});
