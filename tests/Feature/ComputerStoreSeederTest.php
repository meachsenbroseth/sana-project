<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\ComputerStoreSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('it seeds complete computer store demo data', function () {
    $this->seed(ComputerStoreSeeder::class);

    expect(Category::query()->count())->toBeGreaterThanOrEqual(5);
    expect(Brand::query()->count())->toBeGreaterThanOrEqual(6);
    expect(Product::query()->count())->toBeGreaterThanOrEqual(7);
    expect(ProductImage::query()->count())->toBeGreaterThanOrEqual(5);
    expect(Customer::query()->count())->toBeGreaterThanOrEqual(3);
    expect(Order::query()->count())->toBeGreaterThanOrEqual(3);
    expect(OrderItem::query()->count())->toBeGreaterThanOrEqual(5);
    expect(OrderStatusHistory::query()->count())->toBeGreaterThanOrEqual(7);
    expect(Review::query()->count())->toBeGreaterThanOrEqual(3);
    expect(ShippingMethod::query()->count())->toBeGreaterThanOrEqual(4);
    expect(Setting::query()->count())->toBeGreaterThanOrEqual(5);
    expect(SiteSetting::query()->count())->toBe(1);
    expect(User::query()->where('email', 'admin@example.com')->exists())->toBeTrue();
    expect(Role::query()->where('name', 'admin')->exists())->toBeTrue();
    expect(Permission::query()->count())->toBeGreaterThanOrEqual(5);
    expect(DatabaseNotification::query()->count())->toBeGreaterThanOrEqual(2);

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

    $countsAfterFirstRun = [
        'categories' => Category::query()->count(),
        'brands' => Brand::query()->count(),
        'products' => Product::query()->count(),
        'customers' => Customer::query()->count(),
        'orders' => Order::query()->count(),
        'order_items' => OrderItem::query()->count(),
        'reviews' => Review::query()->count(),
        'shipping_methods' => ShippingMethod::query()->count(),
        'settings' => Setting::query()->count(),
        'roles' => Role::query()->count(),
        'permissions' => Permission::query()->count(),
        'notifications' => DatabaseNotification::query()->count(),
    ];

    $this->seed(ComputerStoreSeeder::class);

    expect(Category::query()->count())->toBe($countsAfterFirstRun['categories']);
    expect(Brand::query()->count())->toBe($countsAfterFirstRun['brands']);
    expect(Product::query()->count())->toBe($countsAfterFirstRun['products']);
    expect(Customer::query()->count())->toBe($countsAfterFirstRun['customers']);
    expect(Order::query()->count())->toBe($countsAfterFirstRun['orders']);
    expect(OrderItem::query()->count())->toBe($countsAfterFirstRun['order_items']);
    expect(Review::query()->count())->toBe($countsAfterFirstRun['reviews']);
    expect(ShippingMethod::query()->count())->toBe($countsAfterFirstRun['shipping_methods']);
    expect(Setting::query()->count())->toBe($countsAfterFirstRun['settings']);
    expect(Role::query()->count())->toBe($countsAfterFirstRun['roles']);
    expect(Permission::query()->count())->toBe($countsAfterFirstRun['permissions']);
    expect(DatabaseNotification::query()->count())->toBe($countsAfterFirstRun['notifications']);
});
