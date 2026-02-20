<?php

use Livewire\Component;
use App\Models\Product;

new class extends Component {
    public function with()
    {
        // Fetch featured products
        $featuredProducts = Product::with('primeImage')->where('is_active', true)->where('is_featured', true)->inRandomOrder()->take(10)->get();

        // Fetch newest products
        $newArrivals = Product::with('primeImage')->where('is_active', true)->latest()->take(10)->get();

        return [
            'featuredProducts' => $featuredProducts,
            'newArrivals' => $newArrivals,
        ];
    }
};
?>


<div class="bg-[#faf9f6] min-h-screen pb-12">

    <div
        class="w-full relative shadow-sm min-h-[400px] sm:min-h-[500px] lg:min-h-[650px] flex items-center mb-10 sm:mb-16">

        <div class="absolute inset-0 bg-gradient-to-r from-blue-900 to-indigo-800"></div>

        <div class="relative w-full mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-2xl">

                <h2 class="text-4xl sm:text-5xl lg:text-7xl font-extrabold text-white mb-6 leading-tight">Next-Gen Gaming
                    <br> Gear Setup.</h2>

                <p class="text-blue-100 mb-10 text-base sm:text-lg lg:text-xl leading-relaxed">Upgrade your battle
                    station with the latest high-performance components available at Phanna Computer.</p>

                <div>
                    <a href="{{ route('products.index') }}"
                        class="inline-block px-10 py-4 rounded-full text-base lg:text-lg font-bold text-white shadow-xl transition-all duration-500 ease-in-out bg-white/10 backdrop-blur-md border border-white/30 hover:bg-[#010F1C] hover:border-[#010F1C] hover:shadow-2xl hover:-translate-y-1">
                        Shop Now
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        @if ($featuredProducts->count() > 0)
            <div class="mb-10 sm:mb-12">
                <div
                    class="flex flex-wrap gap-2 justify-between items-end border-b border-gray-200 pb-3 sm:pb-4 mb-5 sm:mb-6">
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900">Featured Products</h3>
                    <a href="{{ route('products.index') }}"
                        class="text-xs sm:text-sm font-semibold text-blue-600 hover:text-blue-800">View All →</a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
                    @foreach ($featuredProducts as $product)
                        <livewire:product-card :key="'featured-' . $product->id" :product="$product" />
                    @endforeach
                </div>
            </div>
        @endif

        @if ($newArrivals->count() > 0)
            <div class="mb-10 sm:mb-12">
                <div
                    class="flex flex-wrap gap-2 justify-between items-end border-b border-gray-200 pb-3 sm:pb-4 mb-5 sm:mb-6">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">New Arrivals</h2>
                    <a href="{{ route('products.index') }}"
                        class="text-xs sm:text-sm font-semibold text-blue-600 hover:text-blue-800">View All →</a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
                    @foreach ($newArrivals as $product)
                        <livewire:product-card :key="'new-' . $product->id" :product="$product" />
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
