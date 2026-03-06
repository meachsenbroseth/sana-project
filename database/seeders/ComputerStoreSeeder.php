<?php

namespace Database\Seeders;

use App\Models\Address;
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
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ComputerStoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsersAndRoles();
        $this->seedSettings();
        $categoryMap = $this->seedCategories();
        $brandMap = $this->seedBrands();
        $productMap = $this->seedProducts($categoryMap, $brandMap);
        $this->seedProductImages($productMap);
        $customerMap = $this->seedCustomers();
        $this->seedAddresses($customerMap);
        $orderMap = $this->seedOrders($customerMap);
        $this->seedOrderItems($orderMap, $productMap);
        $this->seedOrderStatusHistory($orderMap);
        $this->seedReviews($customerMap, $productMap, $orderMap);
        $this->seedShippingMethods();
        $this->seedSiteSettings();
        $this->seedNotifications();
    }

    /**
     * @return array<string, \App\Models\Category>
     */
    private function seedCategories(): array
    {
        $rows = [
            ['name' => 'Laptops', 'slug' => 'laptops', 'is_active' => true, 'sort_order' => 1, 'meta_title' => 'Laptops', 'meta_description' => 'Portable computers for work and gaming.'],
            ['name' => 'Desktop PCs', 'slug' => 'desktop-pcs', 'is_active' => true, 'sort_order' => 2, 'meta_title' => 'Desktop PCs', 'meta_description' => 'High-performance desktop computers.'],
            ['name' => 'Computer Components', 'slug' => 'computer-components', 'is_active' => true, 'sort_order' => 3, 'meta_title' => 'Components', 'meta_description' => 'Parts and upgrades for your build.'],
            ['name' => 'Monitors', 'slug' => 'monitors', 'is_active' => true, 'sort_order' => 4, 'meta_title' => 'Monitors', 'meta_description' => 'Gaming and productivity displays.'],
            ['name' => 'Gaming Accessories', 'slug' => 'gaming-accessories', 'is_active' => true, 'sort_order' => 5, 'meta_title' => 'Accessories', 'meta_description' => 'Peripherals for gaming setups.'],
        ];

        $map = [];
        foreach ($rows as $row) {
            $category = Category::query()->updateOrCreate(['slug' => $row['slug']], $row);
            $map[$row['slug']] = $category;
        }

        return $map;
    }

    /**
     * @return array<string, \App\Models\Brand>
     */
    private function seedBrands(): array
    {
        $rows = [
            ['name' => 'Dell', 'slug' => 'dell', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'HP', 'slug' => 'hp', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'ASUS', 'slug' => 'asus', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Intel', 'slug' => 'intel', 'is_active' => true, 'sort_order' => 4],
            ['name' => 'MSI', 'slug' => 'msi', 'is_active' => true, 'sort_order' => 5],
            ['name' => 'Logitech', 'slug' => 'logitech', 'is_active' => true, 'sort_order' => 6],
        ];

        $map = [];
        foreach ($rows as $row) {
            $brand = Brand::query()->updateOrCreate(['slug' => $row['slug']], $row);
            $map[$row['slug']] = $brand;
        }

        return $map;
    }

    /**
     * @param  array<string, \App\Models\Category>  $categoryMap
     * @param  array<string, \App\Models\Brand>  $brandMap
     * @return array<string, \App\Models\Product>
     */
    private function seedProducts(array $categoryMap, array $brandMap): array
    {
        $rows = [
            // --- Laptops ---
            ['name' => 'Dell XPS 15', 'slug' => 'dell-xps-15', 'sku' => 'LAP-DELL-XPS15', 'category_slug' => 'laptops', 'brand_slug' => 'dell', 'description' => '15-inch premium laptop.', 'price' => 1699.99, 'compare_price' => 1799.99, 'cost_price' => 1400.00, 'stock_quantity' => 20, 'low_stock_threshold' => 5, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],
            ['name' => 'ASUS ROG Zephyrus G14', 'slug' => 'asus-rog-zephyrus-g14', 'sku' => 'LAP-ASUS-G14', 'category_slug' => 'laptops', 'brand_slug' => 'asus', 'description' => 'Ultra-portable 14-inch gaming laptop with Ryzen 9.', 'price' => 1499.00, 'compare_price' => 1649.00, 'cost_price' => 1250.00, 'stock_quantity' => 15, 'low_stock_threshold' => 3, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],
            ['name' => 'HP Spectre x360', 'slug' => 'hp-spectre-x360', 'sku' => 'LAP-HP-SPEC360', 'category_slug' => 'laptops', 'brand_slug' => 'hp', 'description' => 'Premium 2-in-1 convertible laptop.', 'price' => 1349.99, 'compare_price' => 1499.99, 'cost_price' => 1100.00, 'stock_quantity' => 10, 'low_stock_threshold' => 2, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => false],
            ['name' => 'MSI Stealth 16 Studio', 'slug' => 'msi-stealth-16', 'sku' => 'LAP-MSI-ST16', 'category_slug' => 'laptops', 'brand_slug' => 'msi', 'description' => 'Slim and powerful laptop for creators and gamers.', 'price' => 1899.00, 'compare_price' => 1999.00, 'cost_price' => 1600.00, 'stock_quantity' => 8, 'low_stock_threshold' => 2, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],

            // --- Desktop PCs ---
            ['name' => 'HP Pavilion Gaming Desktop', 'slug' => 'hp-pavilion-gaming-desktop', 'sku' => 'DESK-HP-PAVILION', 'category_slug' => 'desktop-pcs', 'brand_slug' => 'hp', 'description' => 'Gaming desktop with dedicated graphics.', 'price' => 1199.99, 'compare_price' => 1299.99, 'cost_price' => 980.00, 'stock_quantity' => 12, 'low_stock_threshold' => 4, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],
            ['name' => 'MSI Infinite RS Desktop', 'slug' => 'msi-infinite-rs-desktop', 'sku' => 'DESK-MSI-INFINITE-RS', 'category_slug' => 'desktop-pcs', 'brand_slug' => 'msi', 'description' => 'Workstation-grade desktop for creators.', 'price' => 2399.00, 'compare_price' => 2599.00, 'cost_price' => 2100.00, 'stock_quantity' => 7, 'low_stock_threshold' => 2, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],
            ['name' => 'Dell Alienware Aurora R15', 'slug' => 'dell-alienware-aurora-r15', 'sku' => 'DESK-DELL-AURR15', 'category_slug' => 'desktop-pcs', 'brand_slug' => 'dell', 'description' => 'High-performance liquid-cooled gaming desktop.', 'price' => 2899.99, 'compare_price' => null, 'cost_price' => 2400.00, 'stock_quantity' => 4, 'low_stock_threshold' => 2, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],

            // --- Computer Components ---
            ['name' => 'ASUS GeForce RTX 4070', 'slug' => 'asus-geforce-rtx-4070', 'sku' => 'COMP-ASUS-RTX4070', 'category_slug' => 'computer-components', 'brand_slug' => 'asus', 'description' => 'High-end graphics card for 1440p gaming.', 'price' => 599.99, 'compare_price' => 649.99, 'cost_price' => 520.00, 'stock_quantity' => 15, 'low_stock_threshold' => 5, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],
            ['name' => 'Intel Core i7-14700K', 'slug' => 'intel-core-i7-14700k', 'sku' => 'COMP-INTEL-I714700K', 'category_slug' => 'computer-components', 'brand_slug' => 'intel', 'description' => 'Desktop processor for demanding workloads.', 'price' => 429.99, 'compare_price' => 469.99, 'cost_price' => 360.00, 'stock_quantity' => 25, 'low_stock_threshold' => 8, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => false],
            ['name' => 'Intel Core i9-14900K', 'slug' => 'intel-core-i9-14900k', 'sku' => 'COMP-INTEL-I914900K', 'category_slug' => 'computer-components', 'brand_slug' => 'intel', 'description' => 'Flagship 24-core desktop processor.', 'price' => 589.99, 'compare_price' => 629.99, 'cost_price' => 490.00, 'stock_quantity' => 10, 'low_stock_threshold' => 3, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],
            ['name' => 'MSI MAG B650 Tomahawk WiFi', 'slug' => 'msi-mag-b650-tomahawk', 'sku' => 'COMP-MSI-B650', 'category_slug' => 'computer-components', 'brand_slug' => 'msi', 'description' => 'ATX Motherboard for AMD Ryzen 7000 Series.', 'price' => 219.99, 'compare_price' => 239.99, 'cost_price' => 175.00, 'stock_quantity' => 30, 'low_stock_threshold' => 10, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => false],

            // --- Monitors ---
            ['name' => 'Dell 27-inch QHD Monitor', 'slug' => 'dell-27-inch-qhd-monitor', 'sku' => 'MON-DELL-27-QHD', 'category_slug' => 'monitors', 'brand_slug' => 'dell', 'description' => '27-inch IPS monitor with QHD resolution.', 'price' => 329.00, 'compare_price' => 359.00, 'cost_price' => 280.00, 'stock_quantity' => 16, 'low_stock_threshold' => 5, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],
            ['name' => 'ASUS ROG Swift 360Hz', 'slug' => 'asus-rog-swift-360hz', 'sku' => 'MON-ASUS-ROG360', 'category_slug' => 'monitors', 'brand_slug' => 'asus', 'description' => '24.5-inch 1080p Esports gaming monitor.', 'price' => 499.00, 'compare_price' => 549.00, 'cost_price' => 410.00, 'stock_quantity' => 8, 'low_stock_threshold' => 2, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],
            ['name' => 'HP OMEN 27i', 'slug' => 'hp-omen-27i', 'sku' => 'MON-HP-OMEN27', 'category_slug' => 'monitors', 'brand_slug' => 'hp', 'description' => '27-inch 165Hz Nano IPS Gaming Monitor.', 'price' => 399.99, 'compare_price' => 449.99, 'cost_price' => 310.00, 'stock_quantity' => 22, 'low_stock_threshold' => 6, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => false],

            // --- Gaming Accessories ---
            ['name' => 'Logitech G Pro X Superlight', 'slug' => 'logitech-g-pro-x-superlight', 'sku' => 'ACC-LOGI-GPROX-SL', 'category_slug' => 'gaming-accessories', 'brand_slug' => 'logitech', 'description' => 'Wireless ultra-light gaming mouse.', 'price' => 129.00, 'compare_price' => 149.00, 'cost_price' => 102.00, 'stock_quantity' => 40, 'low_stock_threshold' => 10, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],
            ['name' => 'Logitech G915 TKL', 'slug' => 'logitech-g915-tkl', 'sku' => 'ACC-LOGI-G915', 'category_slug' => 'gaming-accessories', 'brand_slug' => 'logitech', 'description' => 'Tenkeyless lightspeed wireless mechanical keyboard.', 'price' => 199.99, 'compare_price' => 229.99, 'cost_price' => 150.00, 'stock_quantity' => 25, 'low_stock_threshold' => 5, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => true],
            ['name' => 'Logitech G502 Hero', 'slug' => 'logitech-g502-hero', 'sku' => 'ACC-LOGI-G502', 'category_slug' => 'gaming-accessories', 'brand_slug' => 'logitech', 'description' => 'High performance wired gaming mouse with adjustable weights.', 'price' => 49.99, 'compare_price' => 79.99, 'cost_price' => 35.00, 'stock_quantity' => 50, 'low_stock_threshold' => 15, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => false],
            ['name' => 'Dell Alienware AW410K Keyboard', 'slug' => 'dell-alienware-aw410k', 'sku' => 'ACC-DELL-AW410K', 'category_slug' => 'gaming-accessories', 'brand_slug' => 'dell', 'description' => 'RGB mechanical gaming keyboard with Cherry MX Brown switches.', 'price' => 109.99, 'compare_price' => 129.99, 'cost_price' => 85.00, 'stock_quantity' => 18, 'low_stock_threshold' => 4, 'manage_stock' => true, 'stock_status' => 'in_stock', 'status' => 'new', 'is_active' => true, 'is_featured' => false],
        ];

        $map = [];
        foreach ($rows as $row) {
            $category = $categoryMap[$row['category_slug']];
            $brand = $brandMap[$row['brand_slug']];
            unset($row['category_slug'], $row['brand_slug']);

            $product = Product::query()->updateOrCreate(
                ['sku' => $row['sku']],
                array_merge($row, [
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'meta_title' => $row['name'],
                    'meta_description' => $row['description'],
                ])
            );

            $map[$product->sku] = $product;
        }

        return $map;
    }

    /**
     * @param  array<string, \App\Models\Product>  $productMap
     */
    private function seedProductImages(array $productMap): void
    {
        $rows = [
            // Laptops
            ['sku' => 'LAP-DELL-XPS15', 'image_path' => 'products/dell-xps-15-front.jpg', 'alt_text' => 'Dell XPS 15 front', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'LAP-ASUS-G14', 'image_path' => 'products/asus-g14-front.jpg', 'alt_text' => 'ASUS ROG G14', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'LAP-HP-SPEC360', 'image_path' => 'products/hp-spectre-360.jpg', 'alt_text' => 'HP Spectre x360', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'LAP-MSI-ST16', 'image_path' => 'products/msi-stealth-16.jpg', 'alt_text' => 'MSI Stealth 16 Studio', 'is_primary' => true, 'sort_order' => 1],

            // Desktops
            ['sku' => 'DESK-HP-PAVILION', 'image_path' => 'products/hp-pavilion-desktop.jpg', 'alt_text' => 'HP Pavilion desktop', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'DESK-MSI-INFINITE-RS', 'image_path' => 'products/msi-infinite-rs.jpg', 'alt_text' => 'MSI Infinite RS desktop', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'DESK-DELL-AURR15', 'image_path' => 'products/dell-aurora-r15.jpg', 'alt_text' => 'Dell Alienware Aurora R15', 'is_primary' => true, 'sort_order' => 1],

            // Components
            ['sku' => 'COMP-ASUS-RTX4070', 'image_path' => 'products/asus-rtx-4070.jpg', 'alt_text' => 'ASUS RTX 4070', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'COMP-INTEL-I714700K', 'image_path' => 'products/intel-i7-14700k.jpg', 'alt_text' => 'Intel Core i7-14700K', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'COMP-INTEL-I914900K', 'image_path' => 'products/intel-i9-14900k.jpg', 'alt_text' => 'Intel Core i9-14900K', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'COMP-MSI-B650', 'image_path' => 'products/msi-b650-tomahawk.jpg', 'alt_text' => 'MSI MAG B650 Motherboard', 'is_primary' => true, 'sort_order' => 1],

            // Monitors
            ['sku' => 'MON-DELL-27-QHD', 'image_path' => 'products/dell-27-qhd-monitor.jpg', 'alt_text' => 'Dell QHD monitor', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'MON-ASUS-ROG360', 'image_path' => 'products/asus-rog-360hz.jpg', 'alt_text' => 'ASUS ROG 360Hz Monitor', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'MON-HP-OMEN27', 'image_path' => 'products/hp-omen-27i.jpg', 'alt_text' => 'HP OMEN 27i Monitor', 'is_primary' => true, 'sort_order' => 1],

            // Accessories
            ['sku' => 'ACC-LOGI-GPROX-SL', 'image_path' => 'products/logitech-superlight.jpg', 'alt_text' => 'Logitech G Pro X Superlight', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'ACC-LOGI-G915', 'image_path' => 'products/logitech-g915.jpg', 'alt_text' => 'Logitech G915 Keyboard', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'ACC-LOGI-G502', 'image_path' => 'products/logitech-g502.jpg', 'alt_text' => 'Logitech G502 Hero Mouse', 'is_primary' => true, 'sort_order' => 1],
            ['sku' => 'ACC-DELL-AW410K', 'image_path' => 'products/dell-aw410k.jpg', 'alt_text' => 'Alienware AW410K Keyboard', 'is_primary' => true, 'sort_order' => 1],
        ];

        foreach ($rows as $row) {
            $product = $productMap[$row['sku']] ?? null;
            if (! $product) {
                continue;
            }

            ProductImage::query()->updateOrCreate(
                ['product_id' => $product->id, 'image_path' => $row['image_path']],
                ['alt_text' => $row['alt_text'], 'is_primary' => $row['is_primary'], 'sort_order' => $row['sort_order']]
            );
        }
    }

    /**
     * @return array<string, \App\Models\Customer>
     */
    private function seedCustomers(): array
    {
        $rows = [
            ['name' => 'Sok Dara', 'email' => 'sok.dara@example.com', 'phone' => '012345601', 'gender' => 'male'],
            ['name' => 'Chanthou Kim', 'email' => 'chanthou.kim@example.com', 'phone' => '012345602', 'gender' => 'female'],
            ['name' => 'Piseth Chea', 'email' => 'piseth.chea@example.com', 'phone' => '012345603', 'gender' => 'male'],
        ];

        $map = [];
        foreach ($rows as $row) {
            $customer = Customer::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                    'phone' => $row['phone'],
                    'gender' => $row['gender'],
                    'is_active' => true,
                    'email_verified_at' => now()->subDays(30),
                ]
            );
            $map[$customer->email] = $customer;
        }

        return $map;
    }

    /**
     * @param  array<string, \App\Models\Customer>  $customerMap
     */
    private function seedAddresses(array $customerMap): void
    {
        $rows = [
            ['customer_email' => 'sok.dara@example.com', 'full_name' => 'Sok Dara', 'phone' => '012345601', 'address_line_1' => 'St 271', 'address_line_2' => 'Tuol Tumpung', 'city' => 'Phnom Penh', 'state' => null, 'country' => 'KH', 'is_default' => true],
            ['customer_email' => 'chanthou.kim@example.com', 'full_name' => 'Chanthou Kim', 'phone' => '012345602', 'address_line_1' => 'St 155', 'address_line_2' => 'Boeung Keng Kang', 'city' => 'Phnom Penh', 'state' => null, 'country' => 'KH', 'is_default' => true],
            ['customer_email' => 'piseth.chea@example.com', 'full_name' => 'Piseth Chea', 'phone' => '012345603', 'address_line_1' => 'Sangkat Sla Kram', 'address_line_2' => null, 'city' => 'Siem Reap', 'state' => null, 'country' => 'KH', 'is_default' => true],
        ];

        foreach ($rows as $row) {
            $customer = $customerMap[$row['customer_email']] ?? null;
            if (! $customer) {
                continue;
            }

            $addressData = $row;
            unset($addressData['customer_email']);

            Address::query()->updateOrCreate(
                ['customer_id' => $customer->id, 'address_line_1' => $row['address_line_1']],
                array_merge($addressData, ['customer_id' => $customer->id])
            );
        }
    }

    /**
     * @param  array<string, \App\Models\Customer>  $customerMap
     * @return array<string, \App\Models\Order>
     */
    private function seedOrders(array $customerMap): array
    {
        $rows = [
            ['order_number' => 'ORD-DEMO-1001', 'customer_email' => 'sok.dara@example.com', 'subtotal' => 1699.99, 'discount_amount' => 50.00, 'shipping_cost' => 3.00, 'total' => 1652.99, 'shipping_method' => 'Express Delivery', 'shipping_full_name' => 'Sok Dara', 'shipping_phone' => '012345601', 'shipping_address_line_1' => 'St 271', 'shipping_address_line_2' => 'Tuol Tumpung', 'shipping_city' => 'Phnom Penh', 'shipping_state' => null, 'shipping_country' => 'KH', 'payment_method' => 'KHQR', 'payment_status' => 'paid', 'transaction_id' => 'KHQR-DEMO-1001', 'status' => 'delivered', 'tracking_number' => 'TRK-DEMO-1001', 'customer_notes' => 'Call before delivery', 'admin_notes' => 'Delivered'],
            ['order_number' => 'ORD-DEMO-1002', 'customer_email' => 'chanthou.kim@example.com', 'subtotal' => 1728.99, 'discount_amount' => 0.00, 'shipping_cost' => 3.00, 'total' => 1731.99, 'shipping_method' => 'Standard Delivery', 'shipping_full_name' => 'Chanthou Kim', 'shipping_phone' => '012345602', 'shipping_address_line_1' => 'St 155', 'shipping_address_line_2' => 'Boeung Keng Kang', 'shipping_city' => 'Phnom Penh', 'shipping_state' => null, 'shipping_country' => 'KH', 'payment_method' => 'cash_on_delivery', 'payment_status' => 'pending', 'transaction_id' => null, 'status' => 'processing', 'tracking_number' => null, 'customer_notes' => null, 'admin_notes' => 'Packing'],
            ['order_number' => 'ORD-DEMO-1003', 'customer_email' => 'piseth.chea@example.com', 'subtotal' => 2399.00, 'discount_amount' => 100.00, 'shipping_cost' => 5.00, 'total' => 2304.00, 'shipping_method' => 'Express Delivery', 'shipping_full_name' => 'Piseth Chea', 'shipping_phone' => '012345603', 'shipping_address_line_1' => 'Sangkat Sla Kram', 'shipping_address_line_2' => null, 'shipping_city' => 'Siem Reap', 'shipping_state' => null, 'shipping_country' => 'KH', 'payment_method' => 'cash_on_delivery', 'payment_status' => 'pending', 'transaction_id' => null, 'status' => 'pending', 'tracking_number' => null, 'customer_notes' => 'After 6 PM', 'admin_notes' => null],
        ];

        $map = [];
        foreach ($rows as $row) {
            $customer = $customerMap[$row['customer_email']];
            unset($row['customer_email']);
            $order = Order::query()->updateOrCreate(['order_number' => $row['order_number']], array_merge($row, ['customer_id' => $customer->id]));
            $map[$order->order_number] = $order;
        }

        return $map;
    }

    /**
     * @param  array<string, \App\Models\Order>  $orderMap
     * @param  array<string, \App\Models\Product>  $productMap
     */
    private function seedOrderItems(array $orderMap, array $productMap): void
    {
        $rows = [
            ['order_number' => 'ORD-DEMO-1001', 'sku' => 'LAP-DELL-XPS15', 'quantity' => 1, 'unit_amount' => 1699.99],
            ['order_number' => 'ORD-DEMO-1002', 'sku' => 'COMP-ASUS-RTX4070', 'quantity' => 1, 'unit_amount' => 599.99],
            ['order_number' => 'ORD-DEMO-1002', 'sku' => 'COMP-INTEL-I714700K', 'quantity' => 1, 'unit_amount' => 429.99],
            ['order_number' => 'ORD-DEMO-1002', 'sku' => 'MON-DELL-27-QHD', 'quantity' => 1, 'unit_amount' => 329.00],
            ['order_number' => 'ORD-DEMO-1003', 'sku' => 'DESK-MSI-INFINITE-RS', 'quantity' => 1, 'unit_amount' => 2399.00],
        ];

        foreach ($rows as $row) {
            $order = $orderMap[$row['order_number']] ?? null;
            $product = $productMap[$row['sku']] ?? null;
            if (! $order || ! $product) {
                continue;
            }

            OrderItem::query()->updateOrCreate(
                ['order_id' => $order->id, 'product_id' => $product->id],
                [
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $row['quantity'],
                    'unit_amount' => $row['unit_amount'],
                    'total_amount' => $row['quantity'] * $row['unit_amount'],
                ]
            );
        }
    }

    /**
     * @param  array<string, \App\Models\Order>  $orderMap
     */
    private function seedOrderStatusHistory(array $orderMap): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();

        $rows = [
            ['order_number' => 'ORD-DEMO-1001', 'status' => 'pending', 'notes' => 'Order received.'],
            ['order_number' => 'ORD-DEMO-1001', 'status' => 'processing', 'notes' => 'Payment confirmed.'],
            ['order_number' => 'ORD-DEMO-1001', 'status' => 'shipped', 'notes' => 'Dispatched to courier.'],
            ['order_number' => 'ORD-DEMO-1001', 'status' => 'delivered', 'notes' => 'Delivered to customer.'],
            ['order_number' => 'ORD-DEMO-1002', 'status' => 'pending', 'notes' => 'Order received.'],
            ['order_number' => 'ORD-DEMO-1002', 'status' => 'processing', 'notes' => 'Preparing package.'],
            ['order_number' => 'ORD-DEMO-1003', 'status' => 'pending', 'notes' => 'Awaiting customer confirmation.'],
        ];

        foreach ($rows as $row) {
            $order = $orderMap[$row['order_number']] ?? null;
            if (! $order) {
                continue;
            }

            OrderStatusHistory::query()->updateOrCreate(
                ['order_id' => $order->id, 'status' => $row['status'], 'notes' => $row['notes']],
                ['user_id' => $admin?->id]
            );
        }
    }

    /**
     * @param  array<string, \App\Models\Customer>  $customerMap
     * @param  array<string, \App\Models\Product>  $productMap
     * @param  array<string, \App\Models\Order>  $orderMap
     */
    private function seedReviews(array $customerMap, array $productMap, array $orderMap): void
    {
        $rows = [
            ['customer_email' => 'sok.dara@example.com', 'sku' => 'LAP-DELL-XPS15', 'order_number' => 'ORD-DEMO-1001', 'rating' => 5, 'title' => 'Great performance', 'comment' => 'Excellent laptop for work and gaming.', 'is_verified_purchase' => true, 'is_approved' => true],
            ['customer_email' => 'chanthou.kim@example.com', 'sku' => 'MON-DELL-27-QHD', 'order_number' => 'ORD-DEMO-1002', 'rating' => 4, 'title' => 'Sharp display', 'comment' => 'Crisp panel and smooth refresh rate.', 'is_verified_purchase' => true, 'is_approved' => true],
            ['customer_email' => 'piseth.chea@example.com', 'sku' => 'DESK-MSI-INFINITE-RS', 'order_number' => 'ORD-DEMO-1003', 'rating' => 5, 'title' => 'Very fast desktop', 'comment' => 'Great for rendering and development.', 'is_verified_purchase' => true, 'is_approved' => false],
        ];

        foreach ($rows as $row) {
            $customer = $customerMap[$row['customer_email']] ?? null;
            $product = $productMap[$row['sku']] ?? null;
            $order = $orderMap[$row['order_number']] ?? null;
            if (! $customer || ! $product) {
                continue;
            }

            Review::query()->updateOrCreate(
                ['product_id' => $product->id, 'customer_id' => $customer->id],
                [
                    'order_id' => $order?->id,
                    'rating' => $row['rating'],
                    'title' => $row['title'],
                    'comment' => $row['comment'],
                    'is_verified_purchase' => $row['is_verified_purchase'],
                    'is_approved' => $row['is_approved'],
                ]
            );
        }
    }

    private function seedShippingMethods(): void
    {
        $rows = [
            ['name' => 'Standard Delivery', 'cost' => 3.00, 'status' => 'active'],
            ['name' => 'Express Delivery', 'cost' => 6.00, 'status' => 'active'],
            ['name' => 'Pickup at Store', 'cost' => 0.00, 'status' => 'active'],
            ['name' => 'Free Delivery', 'cost' => 0.00, 'status' => 'inactive'],
        ];

        foreach ($rows as $row) {
            ShippingMethod::query()->updateOrCreate(['name' => $row['name']], $row);
        }
    }

    private function seedSiteSettings(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'banner_image' => 'banners/homepage-banner-1.jpg',
                'banner_images' => [
                    [
                        'image' => 'banners/homepage-banner-1.jpg',
                        'title' => 'Work Smarter with Premium Laptops',
                        'link' => null,
                        'status' => 'active',
                        'sort_order' => 1,
                    ],
                    [
                        'image' => 'banners/homepage-banner-2.jpg',
                        'title' => 'Build Your Dream Gaming Setup',
                        'link' => null,
                        'status' => 'active',
                        'sort_order' => 2,
                    ],
                    [
                        'image' => 'banners/homepage-banner-3.jpg',
                        'title' => 'Latest Accessories at Great Prices',
                        'link' => null,
                        'status' => 'active',
                        'sort_order' => 3,
                    ],
                ],
                'updated_at' => now(),
            ]
        );
    }

    private function seedSettings(): void
    {
        $rows = [
            ['key' => 'store_name', 'value' => 'Phanna Computer', 'type' => 'string', 'group' => 'general'],
            ['key' => 'support_phone', 'value' => '012689168', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'support_email', 'value' => 'support@phannacomputer.com', 'type' => 'string', 'group' => 'contact'],
            ['key' => 'currency_code', 'value' => 'USD', 'type' => 'string', 'group' => 'checkout'],
            ['key' => 'tax_rate', 'value' => '0', 'type' => 'number', 'group' => 'checkout'],
        ];

        foreach ($rows as $row) {
            Setting::query()->updateOrCreate(['key' => $row['key']], $row);
        }
    }

    private function seedUsersAndRoles(): void
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'phone' => '010111111'],
            ['name' => 'Operations Manager', 'email' => 'manager@example.com', 'phone' => '010222222'],
            ['name' => 'Support Agent', 'email' => 'support@example.com', 'phone' => '010333333'],
        ];

        foreach ($users as $row) {
            User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now()->subDays(60),
                    'is_active' => true,
                ]
            );
        }

        $permissionNames = ['manage products', 'manage orders', 'manage customers', 'manage settings', 'manage reviews'];
        foreach ($permissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $managerRole = Role::findOrCreate('manager', 'web');
        $supportRole = Role::findOrCreate('support', 'web');
        $adminRole->syncPermissions($permissionNames);
        $managerRole->syncPermissions(['manage products', 'manage orders', 'manage customers', 'manage reviews']);
        $supportRole->syncPermissions(['manage orders', 'manage customers']);

        User::query()->where('email', 'admin@example.com')->first()?->syncRoles([$adminRole]);
        User::query()->where('email', 'manager@example.com')->first()?->syncRoles([$managerRole]);
        User::query()->where('email', 'support@example.com')->first()?->syncRoles([$supportRole]);
    }

    private function seedNotifications(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();
        $manager = User::query()->where('email', 'manager@example.com')->first();

        if (! $admin || ! $manager) {
            return;
        }

        $rows = [
            ['id' => '00000000-0000-0000-0000-000000000101', 'type' => 'App\\Notifications\\OrderPlacedNotification', 'notifiable_type' => User::class, 'notifiable_id' => $admin->id, 'data' => ['title' => 'New order', 'order_number' => 'ORD-DEMO-1003']],
            ['id' => '00000000-0000-0000-0000-000000000102', 'type' => 'App\\Notifications\\LowStockNotification', 'notifiable_type' => User::class, 'notifiable_id' => $manager->id, 'data' => ['title' => 'Low stock', 'sku' => 'DESK-MSI-INFINITE-RS']],
        ];

        foreach ($rows as $row) {
            DatabaseNotification::query()->updateOrCreate(
                ['id' => $row['id']],
                array_merge($row, ['read_at' => null])
            );
        }
    }
}
