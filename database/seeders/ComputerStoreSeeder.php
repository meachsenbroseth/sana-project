<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ComputerStoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Laptops',
                'slug' => 'laptops',
                'is_active' => true,
                'sort_order' => 1,
                'meta_title' => 'Laptops',
                'meta_description' => 'Portable computers for work and gaming.',
            ],
            [
                'name' => 'Desktop PCs',
                'slug' => 'desktop-pcs',
                'is_active' => true,
                'sort_order' => 2,
                'meta_title' => 'Desktop PCs',
                'meta_description' => 'High-performance desktop computers.',
            ],
            [
                'name' => 'Computer Components',
                'slug' => 'computer-components',
                'is_active' => true,
                'sort_order' => 3,
                'meta_title' => 'Computer Components',
                'meta_description' => 'Parts and upgrades for your build.',
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $brands = [
            ['name' => 'Dell', 'slug' => 'dell', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'HP', 'slug' => 'hp', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Lenovo', 'slug' => 'lenovo', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'ASUS', 'slug' => 'asus', 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Intel', 'slug' => 'intel', 'is_active' => true, 'sort_order' => 5],
        ];

        foreach ($brands as $brand) {
            Brand::query()->updateOrCreate(
                ['slug' => $brand['slug']],
                $brand
            );
        }

        $products = [
            [
                'name' => 'Dell XPS 15',
                'slug' => 'dell-xps-15',
                'sku' => 'LAP-DELL-XPS15',
                'category_slug' => 'laptops',
                'brand_slug' => 'dell',
                'description' => '15-inch premium laptop with Intel Core i7 and 16GB RAM.',
                'price' => 1699.99,
                'compare_price' => 1799.99,
                'cost_price' => 1400.00,
                'stock_quantity' => 20,
                'low_stock_threshold' => 5,
                'manage_stock' => true,
                'stock_status' => 'in_stock',
                'status' => 'new',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'HP Pavilion Gaming Desktop',
                'slug' => 'hp-pavilion-gaming-desktop',
                'sku' => 'DESK-HP-PAVILION',
                'category_slug' => 'desktop-pcs',
                'brand_slug' => 'hp',
                'description' => 'Gaming desktop with dedicated graphics and fast SSD storage.',
                'price' => 1199.99,
                'compare_price' => 1299.99,
                'cost_price' => 980.00,
                'stock_quantity' => 12,
                'low_stock_threshold' => 4,
                'manage_stock' => true,
                'stock_status' => 'in_stock',
                'status' => 'new',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Lenovo ThinkPad T14 (Refurbished)',
                'slug' => 'lenovo-thinkpad-t14-refurbished',
                'sku' => 'LAP-LEN-T14-REF',
                'category_slug' => 'laptops',
                'brand_slug' => 'lenovo',
                'description' => 'Business laptop in excellent condition with warranty.',
                'price' => 749.99,
                'compare_price' => 899.99,
                'cost_price' => 620.00,
                'stock_quantity' => 8,
                'low_stock_threshold' => 3,
                'manage_stock' => true,
                'stock_status' => 'in_stock',
                'status' => 'used',
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'ASUS GeForce RTX 4070',
                'slug' => 'asus-geforce-rtx-4070',
                'sku' => 'COMP-ASUS-RTX4070',
                'category_slug' => 'computer-components',
                'brand_slug' => 'asus',
                'description' => 'High-end graphics card for 1440p and 4K gaming.',
                'price' => 599.99,
                'compare_price' => 649.99,
                'cost_price' => 520.00,
                'stock_quantity' => 15,
                'low_stock_threshold' => 5,
                'manage_stock' => true,
                'stock_status' => 'in_stock',
                'status' => 'new',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Intel Core i7-14700K',
                'slug' => 'intel-core-i7-14700k',
                'sku' => 'COMP-INTEL-I714700K',
                'category_slug' => 'computer-components',
                'brand_slug' => 'intel',
                'description' => 'Desktop processor for high-performance workloads and gaming.',
                'price' => 429.99,
                'compare_price' => 469.99,
                'cost_price' => 360.00,
                'stock_quantity' => 25,
                'low_stock_threshold' => 8,
                'manage_stock' => true,
                'stock_status' => 'in_stock',
                'status' => 'new',
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($products as $productData) {
            $category = Category::query()->where('slug', $productData['category_slug'])->firstOrFail();
            $brand = Brand::query()->where('slug', $productData['brand_slug'])->firstOrFail();

            unset($productData['category_slug'], $productData['brand_slug']);

            Product::query()->updateOrCreate(
                ['sku' => $productData['sku']],
                array_merge($productData, [
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'meta_title' => $productData['name'],
                    'meta_description' => $productData['description'],
                ])
            );
        }
    }
}
