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
    $response->assertSee('storage/banners/homepage-banner.jpg');
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
