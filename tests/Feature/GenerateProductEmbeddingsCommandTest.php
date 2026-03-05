<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

test('product toEmbeddingText returns name brand category and description', function () {
    $category = Category::query()->create([
        'name' => 'Laptops',
        'slug' => 'laptops',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $brand = Brand::query()->create([
        'name' => 'Dell',
        'slug' => 'dell',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'XPS 15',
        'slug' => 'xps-15',
        'sku' => 'XPS-15-001',
        'description' => 'High-performance laptop.',
        'price' => 1299,
        'stock_quantity' => 10,
        'low_stock_threshold' => 2,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'status' => 'new',
        'is_active' => true,
        'is_featured' => false,
    ]);

    $product->setRelation('category', $category);
    $product->setRelation('brand', $brand);

    $text = $product->toEmbeddingText();

    expect($text)->toContain('XPS 15')
        ->toContain('Dell')
        ->toContain('Laptops')
        ->toContain('High-performance laptop.');
});

test('generate product embeddings command stores embeddings when run', function () {
    if (config('database.default') !== 'pgsql') {
        $this->markTestSkipped('Product embeddings require PostgreSQL with pgvector.');
    }

    $category = Category::query()->create([
        'name' => 'Monitors',
        'slug' => 'monitors',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $brand = Brand::query()->create([
        'name' => 'ASUS',
        'slug' => 'asus',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'ProArt Display',
        'slug' => 'proart-display',
        'sku' => 'PA-001',
        'description' => 'Professional monitor.',
        'price' => 599,
        'stock_quantity' => 5,
        'low_stock_threshold' => 2,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'status' => 'new',
        'is_active' => true,
        'is_featured' => false,
    ]);

    $fakeEmbedding = array_fill(0, 1536, 0.01);

    Embeddings::fake(function (EmbeddingsPrompt $prompt) use ($fakeEmbedding) {
        return array_map(fn () => $fakeEmbedding, $prompt->inputs);
    });

    $this->artisan('products:generate-embeddings')
        ->assertSuccessful();

    $product = Product::query()->where('sku', 'PA-001')->first();
    expect($product->embedding)->toBeArray()
        ->and($product->embedding)->toHaveCount(1536);
});

test('generate product embeddings command skips products that already have embedding without force', function () {
    if (config('database.default') !== 'pgsql') {
        $this->markTestSkipped('Product embeddings require PostgreSQL with pgvector.');
    }

    $category = Category::query()->create([
        'name' => 'Keyboards',
        'slug' => 'keyboards',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $brand = Brand::query()->create([
        'name' => 'Logitech',
        'slug' => 'logitech',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'MX Keys',
        'slug' => 'mx-keys',
        'sku' => 'MX-K-001',
        'description' => 'Premium keyboard.',
        'price' => 99,
        'stock_quantity' => 20,
        'low_stock_threshold' => 2,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'status' => 'new',
        'is_active' => true,
        'is_featured' => false,
    ]);
    $existingEmbedding = array_fill(0, 1536, 0.1);
    $product->embedding = $existingEmbedding;
    $product->saveQuietly();

    Embeddings::fake()->preventStrayEmbeddings();

    $this->artisan('products:generate-embeddings')
        ->assertSuccessful();

    $product->refresh();
    expect($product->embedding)->toBeArray()
        ->and($product->embedding[0] ?? null)->toBe(0.1);
});
