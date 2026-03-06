<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

test('it searches products by brand text via scout on the product listing page', function () {
    config()->set('scout.driver', 'collection');

    $category = Category::query()->create([
        'name' => 'Laptops',
        'slug' => 'laptops',
    ]);

    $brand = Brand::query()->create([
        'name' => 'Acer',
        'slug' => 'acer',
    ]);

    $otherBrand = Brand::query()->create([
        'name' => 'Logitech',
        'slug' => 'logitech',
    ]);

    Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Nitro 5 Gaming Laptop',
        'slug' => 'nitro-5-gaming-laptop',
        'sku' => 'NITRO-5-001',
        'description' => 'Powerful gaming performance.',
        'price' => 999.99,
        'is_active' => true,
    ]);

    Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $otherBrand->id,
        'name' => 'Wireless Gaming Mouse',
        'slug' => 'wireless-gaming-mouse',
        'sku' => 'MOUSE-001',
        'description' => 'High precision tracking.',
        'price' => 79.99,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.index', ['search' => 'Acer']));

    $response->assertSuccessful();
    $response->assertSee('Nitro 5 Gaming Laptop');
    $response->assertDontSee('Wireless Gaming Mouse');
});
