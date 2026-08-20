<?php
/**
 * Feature tests for checkout page enhancements using Pest.
 */
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\withSession;
use function Pest\Laravel\livewire;
use function Pest\Laravel\session;

uses(RefreshDatabase::class);

it('shows products in the checkout sidebar', function () {
    $product = Product::factory()->create([
        'price' => 10.00,
        'stock_quantity' => 5,
    ]);

    $cartItem = [
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 2,
        'image' => null,
    ];

    // Set session cart and test Livewire component
    session(['cart' => [$cartItem]]);
    livewire('pages::checkout')
        ->assertSee('$20.00')
        ->assertSee($product->name);
});

it('displays validation errors and scrolls to first error on checkout', function () {
    // Empty cart session
    session(['cart' => []]);
    livewire('pages::checkout')
        ->call('nextStep')
        ->assertSet('step', 1)
        ->assertHasErrors([
            'full_name',
            'phone',
            'address_line_1',
            'provinceId',
            'districtId',
            'country',
        ])
        ->assertSee('Please correct the highlighted fields.');
});
