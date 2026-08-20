<?php

use App\Filament\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Resources\Suppliers\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\SupplierPolicy;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function makeSupplierForView(bool $isActive = true): Supplier
{
    return Supplier::factory()->create([
        'name' => 'Test Supplier',
        'contact_name' => 'Jane Doe',
        'email' => 'jane@test.com',
        'phone' => '012345678',
        'address' => '123 Market St',
        'is_active' => $isActive,
    ]);
}

function makeProductForSupplier(Supplier $supplier): Product
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
        'supplier_id' => $supplier->id,
        'name' => fake()->unique()->words(3, true),
        'slug' => fake()->unique()->slug(),
        'sku' => fake()->unique()->bothify('SKU-########'),
        'price' => 50,
        'stock_quantity' => 20,
        'low_stock_threshold' => 5,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'is_active' => true,
        'is_featured' => false,
    ]);
}

// ---------------------------------------------------------------------------
// Resource registration
// ---------------------------------------------------------------------------

test('supplier resource registers index, create, view, and edit pages', function (): void {
    $pages = SupplierResource::getPages();

    expect($pages)
        ->toHaveKey('index')
        ->toHaveKey('create')
        ->toHaveKey('view')
        ->toHaveKey('edit');

    expect($pages['view']->getPage())->toBe(ViewSupplier::class);
});

test('supplier resource registers the products relation manager', function (): void {
    expect(SupplierResource::getRelations())
        ->toContain(ProductsRelationManager::class);
});

// ---------------------------------------------------------------------------
// View page — access
// ---------------------------------------------------------------------------

test('authenticated admin with view permission can access the supplier view page', function (): void {
    $user = User::factory()->create();
    $supplier = makeSupplierForView();

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(ViewSupplier::class, ['record' => $supplier->getRouteKey()])
        ->assertSuccessful();
});

test('unauthenticated user is redirected away from the supplier view page', function (): void {
    $supplier = makeSupplierForView();

    $this->get(SupplierResource::getUrl('view', ['record' => $supplier]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// View page — linked products appear
// ---------------------------------------------------------------------------

test('products linked to a supplier appear in the products relation manager', function (): void {
    $user = User::factory()->create();
    $supplier = makeSupplierForView();
    $product = makeProductForSupplier($supplier);

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertCanSeeTableRecords([$product]);
});

test('a supplier with no products shows an empty relation manager without error', function (): void {
    $user = User::factory()->create();
    $supplier = makeSupplierForView();

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertCanNotSeeTableRecords([])
        ->assertSuccessful();
});

test('only products belonging to the supplier appear in the relation manager', function (): void {
    $user = User::factory()->create();
    $supplier = makeSupplierForView();
    $otherSupplier = makeSupplierForView();
    $myProduct = makeProductForSupplier($supplier);
    $otherProduct = makeProductForSupplier($otherSupplier);

    $this->actingAs($user);
    Gate::before(fn () => true);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertCanSeeTableRecords([$myProduct])
        ->assertCanNotSeeTableRecords([$otherProduct]);
});

// ---------------------------------------------------------------------------
// Shield policy regression
// ---------------------------------------------------------------------------

test('supplier policy denies view when user lacks the View:Supplier permission', function (): void {
    $policy = new SupplierPolicy;
    $user = User::factory()->create();
    $supplier = makeSupplierForView();

    expect($policy->view($user, $supplier))->toBeFalse();
});

test('supplier policy denies viewAny when user lacks the ViewAny:Supplier permission', function (): void {
    $policy = new SupplierPolicy;
    $user = User::factory()->create();

    expect($policy->viewAny($user))->toBeFalse();
});
