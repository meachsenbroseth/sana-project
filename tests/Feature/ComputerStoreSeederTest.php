<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\ComputerStoreSeeder;

test('it seeds computer store categories brands and products', function () {
    $this->seed(ComputerStoreSeeder::class);

    expect(Category::query()->count())->toBe(3);
    expect(Brand::query()->count())->toBe(5);
    expect(Product::query()->count())->toBe(5);

    $product = Product::query()
        ->where('sku', 'LAP-DELL-XPS15')
        ->first();

    expect($product)->not->toBeNull();
    expect($product->category)->not->toBeNull();
    expect($product->brand)->not->toBeNull();
    expect($product->status)->toBe('new');
});

test('computer store seeder is idempotent', function () {
    $this->seed(ComputerStoreSeeder::class);
    $this->seed(ComputerStoreSeeder::class);

    expect(Category::query()->count())->toBe(3);
    expect(Brand::query()->count())->toBe(5);
    expect(Product::query()->count())->toBe(5);
});
