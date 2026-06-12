<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\AnalyticsTableResolver;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;

beforeEach(function (): void {
    Cache::flush();
});

it('resolves date presets for analytics filters', function (): void {
    [$start, $end] = AnalyticsFilters::resolveDateRange(AnalyticsFilters::PRESET_TODAY, null, null);

    expect($start->isToday())->toBeTrue()
        ->and($end->isToday())->toBeTrue();
});

it('calculates kpi metrics from paid orders', function (): void {
    $category = Category::query()->create([
        'name' => 'Laptops',
        'slug' => 'laptops-analytics',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $brand = Brand::query()->create([
        'name' => 'Analytics Brand',
        'slug' => 'analytics-brand',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Analytics Laptop',
        'slug' => 'analytics-laptop',
        'sku' => 'ANA-LAP-001',
        'description' => 'Test product',
        'price' => 100,
        'stock_quantity' => 5,
        'low_stock_threshold' => 2,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'status' => 'new',
        'is_active' => true,
    ]);

    $customer = Customer::query()->create([
        'name' => 'Analytics Customer',
        'email' => 'analytics-customer@example.com',
        'password' => 'password',
        'is_active' => true,
    ]);

    $order = Order::query()->create([
        'customer_id' => $customer->id,
        'subtotal' => 200,
        'discount_amount' => 0,
        'shipping_cost' => 0,
        'total' => 200,
        'payment_method' => 'cash_on_delivery',
        'payment_status' => 'paid',
        'status' => 'delivered',
        'shipping_full_name' => $customer->name,
        'shipping_phone' => '012345678',
        'shipping_address_line_1' => '123 Street',
        'shipping_city' => 'Phnom Penh',
        'shipping_country' => 'Cambodia',
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 2,
        'unit_amount' => 100,
        'total_amount' => 200,
    ]);

    $filters = AnalyticsFilters::fromPageFilters([
        'date_preset' => AnalyticsFilters::PRESET_THIS_MONTH,
    ]);

    $metrics = app(AnalyticsService::class)->kpiMetrics($filters);

    expect($metrics['total_revenue'])->toBe(200.0)
        ->and($metrics['total_orders'])->toBe(1)
        ->and($metrics['total_customers'])->toBe(1)
        ->and($metrics['total_products'])->toBe(1)
        ->and($metrics['average_order_value'])->toBe(200.0);
});

it('returns top selling products for analytics tables', function (): void {
    $category = Category::query()->create([
        'name' => 'Accessories',
        'slug' => 'accessories-analytics',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $brand = Brand::query()->create([
        'name' => 'Top Seller Brand',
        'slug' => 'top-seller-brand',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Analytics Mouse',
        'slug' => 'analytics-mouse',
        'sku' => 'ANA-MSE-001',
        'description' => 'Test mouse',
        'price' => 25,
        'stock_quantity' => 10,
        'low_stock_threshold' => 2,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'status' => 'new',
        'is_active' => true,
    ]);

    $customer = Customer::query()->create([
        'name' => 'Top Seller Customer',
        'email' => 'top-seller@example.com',
        'password' => 'password',
        'is_active' => true,
    ]);

    $order = Order::query()->create([
        'customer_id' => $customer->id,
        'subtotal' => 75,
        'discount_amount' => 0,
        'shipping_cost' => 0,
        'total' => 75,
        'payment_method' => 'KHQR',
        'payment_status' => 'paid',
        'status' => 'processing',
        'shipping_full_name' => $customer->name,
        'shipping_phone' => '012345679',
        'shipping_address_line_1' => '456 Street',
        'shipping_city' => 'Phnom Penh',
        'shipping_country' => 'Cambodia',
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 3,
        'unit_amount' => 25,
        'total_amount' => 75,
    ]);

    $filters = AnalyticsFilters::fromPageFilters([
        'date_preset' => AnalyticsFilters::PRESET_THIS_MONTH,
    ]);

    $topProduct = app(AnalyticsService::class)
        ->topSellingProductsQuery($filters)
        ->first();

    expect($topProduct)->not->toBeNull()
        ->and($topProduct->product_name)->toBe('Analytics Mouse')
        ->and((int) $topProduct->quantity_sold)->toBe(3)
        ->and((int) $topProduct->id)->toBe($product->id);
});

it('does not reference order_items.id in the top selling products table query', function (): void {
    $category = Category::query()->create([
        'name' => 'Query Check',
        'slug' => 'query-check',
        'is_active' => true,
        'sort_order' => 99,
    ]);

    $brand = Brand::query()->create([
        'name' => 'Query Brand',
        'slug' => 'query-brand',
        'is_active' => true,
        'sort_order' => 99,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Query Product',
        'slug' => 'query-product',
        'sku' => 'QRY-001',
        'description' => 'Test',
        'price' => 10,
        'stock_quantity' => 5,
        'low_stock_threshold' => 1,
        'manage_stock' => true,
        'stock_status' => 'in_stock',
        'status' => 'new',
        'is_active' => true,
    ]);

    $customer = Customer::query()->create([
        'name' => 'Query Customer',
        'email' => 'query-customer@example.com',
        'password' => 'password',
        'is_active' => true,
    ]);

    $order = Order::query()->create([
        'customer_id' => $customer->id,
        'subtotal' => 10,
        'discount_amount' => 0,
        'shipping_cost' => 0,
        'total' => 10,
        'payment_method' => 'cash_on_delivery',
        'payment_status' => 'paid',
        'status' => 'delivered',
        'shipping_full_name' => $customer->name,
        'shipping_phone' => '012345670',
        'shipping_address_line_1' => '789 Street',
        'shipping_city' => 'Phnom Penh',
        'shipping_country' => 'Cambodia',
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 1,
        'unit_amount' => 10,
        'total_amount' => 10,
    ]);

    $filters = AnalyticsFilters::fromPageFilters([
        'date_preset' => AnalyticsFilters::PRESET_THIS_MONTH,
    ]);

    $sql = app(AnalyticsService::class)
        ->topSellingProductsEloquentQuery($filters)
        ->orderBy('quantity_sold', 'desc')
        ->toSql();

    expect($sql)->not->toContain('order_items.id');
});

it('resolves the product table name from the model', function (): void {
    $resolver = app(AnalyticsTableResolver::class);

    expect($resolver->product())->toBe(app(Product::class)->getTable());
});

it('returns empty product metrics when the products table is missing', function (): void {
    $tables = Mockery::mock(AnalyticsTableResolver::class, function (MockInterface $mock): void {
        $mock->shouldReceive('isOperational')->andReturn(true);
        $mock->shouldReceive('hasProducts')->andReturn(false);
        $mock->shouldReceive('hasOrders')->andReturn(true);
        $mock->shouldReceive('hasOrderItems')->andReturn(true);
        $mock->shouldReceive('hasCustomers')->andReturn(true);
        $mock->shouldReceive('hasCategories')->andReturn(true);
        $mock->shouldReceive('orderItem')->andReturn(app(OrderItem::class)->getTable());
    });

    app()->instance(AnalyticsTableResolver::class, $tables);

    $filters = AnalyticsFilters::fromPageFilters([
        'date_preset' => AnalyticsFilters::PRESET_THIS_MONTH,
    ]);

    $service = app(AnalyticsService::class);

    expect($service->productsAvailable())->toBeFalse()
        ->and($service->kpiMetrics($filters)['total_products'])->toBe(0)
        ->and($service->productPerformance($filters, 'top_selling'))->toBeEmpty()
        ->and($service->topSellingProductsEloquentQuery($filters)->count())->toBe(0);
});

it('paginates top selling products using the synthetic id column', function (): void {
    $filters = AnalyticsFilters::fromPageFilters([
        'date_preset' => AnalyticsFilters::PRESET_THIS_MONTH,
    ]);

    $records = app(AnalyticsService::class)
        ->topSellingProductsEloquentQuery($filters)
        ->orderBy('quantity_sold', 'desc')
        ->limit(10)
        ->get();

    expect($records->every(fn ($record): bool => filled($record->id)))->toBeTrue();
});
