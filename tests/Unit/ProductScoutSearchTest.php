<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Tests\TestCase;

uses(TestCase::class);

test('it builds the searchable payload with related names', function () {
    $product = new Product([
        'name' => 'Nitro 5 Gaming Laptop',
        'description' => '<p>Powerful gaming performance.</p>',
    ]);

    $product->setAttribute('id', 42);
    $product->setRelation('brand', new Brand(['name' => 'Acer']));
    $product->setRelation('category', new Category(['name' => 'Laptops']));

    expect($product->toSearchableArray())->toBe([
        'id' => '42',
        'name' => 'Nitro 5 Gaming Laptop',
        'brand' => 'Acer',
        'category' => 'Laptops',
        'description' => 'Powerful gaming performance.',
    ]);
});

test('it only marks active products as searchable', function () {
    $activeProduct = new Product(['is_active' => true]);
    $inactiveProduct = new Product(['is_active' => false]);

    expect($activeProduct->shouldBeSearchable())->toBeTrue()
        ->and($inactiveProduct->shouldBeSearchable())->toBeFalse();
});
