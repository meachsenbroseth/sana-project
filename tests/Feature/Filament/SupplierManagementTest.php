<?php

use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\SupplierPolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;

// --- Resource registration ---

test('supplier resource registers index, create, and edit pages', function (): void {
    $pages = SupplierResource::getPages();

    expect($pages)
        ->toHaveKey('index')
        ->toHaveKey('create')
        ->toHaveKey('edit');

    expect($pages['index']->getPage())->toBe(ListSuppliers::class);
    expect($pages['create']->getPage())->toBe(CreateSupplier::class);
    expect($pages['edit']->getPage())->toBe(EditSupplier::class);
});

// --- Navigation ---

test('supplier resource belongs to the catalog navigation group', function (): void {
    app()->setLocale('en');

    expect(SupplierResource::getNavigationGroup())->toBe('Catalog')
        ->and(SupplierResource::getNavigationLabel())->toBe('Supplier');
});

test('supplier resource belongs to the catalog navigation group in khmer', function (): void {
    app()->setLocale('km');

    expect(SupplierResource::getNavigationGroup())->toBe('កាតាឡុក')
        ->and(SupplierResource::getNavigationLabel())->toBe('អ្នកផ្គត់ផ្គង់');
});

// --- Model CRUD ---

test('admin can create a supplier', function (): void {
    $supplier = Supplier::factory()->create([
        'name' => 'Acme Supplies',
        'contact_name' => 'Jane Doe',
        'email' => 'jane@acme.com',
        'phone' => '012345678',
        'address' => '123 Main St',
        'is_active' => true,
    ]);

    expect(Supplier::query()->where('name', 'Acme Supplies')->exists())->toBeTrue()
        ->and($supplier->contact_name)->toBe('Jane Doe')
        ->and($supplier->email)->toBe('jane@acme.com');
});

test('admin can update a supplier', function (): void {
    $supplier = Supplier::factory()->create(['name' => 'Old Name']);

    $supplier->update(['name' => 'New Name']);

    expect($supplier->refresh()->name)->toBe('New Name');
});

test('admin can delete a supplier', function (): void {
    $supplier = Supplier::factory()->create();
    $id = $supplier->id;

    $supplier->delete();

    expect(Supplier::query()->find($id))->toBeNull();
});

test('supplier is_active toggles correctly', function (): void {
    $supplier = Supplier::factory()->create(['is_active' => true]);

    $supplier->update(['is_active' => false]);

    expect($supplier->refresh()->is_active)->toBeFalse();
});

// --- Products relationship ---

test('supplier has many products', function (): void {
    $supplier = Supplier::factory()->create();

    expect($supplier->products())->toBeInstanceOf(HasMany::class);
});

test('deleting a supplier sets product supplier_id to null', function (): void {
    $category = Category::query()->create([
        'name' => fake()->unique()->words(2, true),
        'slug' => fake()->unique()->slug(),
    ]);

    $brand = Brand::query()->create([
        'name' => fake()->unique()->company(),
        'slug' => fake()->unique()->slug(),
    ]);

    $supplier = Supplier::factory()->create();

    $product = Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'supplier_id' => $supplier->id,
        'name' => fake()->unique()->words(3, true),
        'slug' => fake()->unique()->slug(),
        'sku' => fake()->unique()->bothify('SKU-########'),
        'price' => 100,
        'stock_quantity' => 5,
        'manage_stock' => true,
        'status' => 'new',
        'is_active' => true,
        'is_featured' => false,
    ]);

    expect($product->supplier_id)->toBe($supplier->id);

    $supplier->delete();

    expect($product->refresh()->supplier_id)->toBeNull();
});

// --- Policy ---

test('supplier policy gates match expected permission key format', function (): void {
    $policy = new SupplierPolicy;
    $supplier = Supplier::factory()->create();

    $user = User::factory()->create();

    // Without permissions the user should be denied
    expect($policy->viewAny($user))->toBeFalse()
        ->and($policy->view($user, $supplier))->toBeFalse()
        ->and($policy->create($user))->toBeFalse()
        ->and($policy->update($user, $supplier))->toBeFalse()
        ->and($policy->delete($user, $supplier))->toBeFalse();
});
