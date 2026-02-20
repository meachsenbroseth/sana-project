<?php

use Livewire\Component;
use App\Models\Product;

new class extends Component
{
    public function with()
    {
        // Fetch featured products
        $featuredProducts = Product::with('primeImage')
            ->where('is_active', true)
            ->where('is_featured', true)
            ->inRandomOrder()
            ->take(10)
            ->get();

        // Fetch newest products
        $newArrivals = Product::with('primeImage')
            ->where('is_active', true)
            ->latest()
            ->take(10)
            ->get();

        return [
            'featuredProducts' => $featuredProducts,
            'newArrivals' => $newArrivals,
        ];
    }
};
?>

<div class="bg-[#faf9f6] min-h-screen pb-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="pt-6 mb-12">
            <div class="w-full rounded-xl overflow-hidden relative bg-gray-900 shadow-sm aspect-[16/9] sm:aspect-[2/1] lg:aspect-[3/1]">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-900 to-indigo-800 flex flex-col justify-center px-8 sm:px-16 lg:px-24">
                    <span class="text-blue-200 font-bold tracking-wider uppercase text-sm mb-3">Grand Opening</span>
                    <h2 class="text-3xl sm:text-4xl lg:text-6xl font-extrabold text-white mb-4 leading-tight">Next-Gen Gaming <br> Gear Setup.</h2>
                    <p class="text-blue-100 max-w-xl mb-8 sm:text-lg">Upgrade your battle station with the latest high-performance components available at Phanna Computer.</p>
                    <div>
                        <a href="{{ route('products.index') }}" class="inline-block bg-white text-gray-900 px-8 py-3.5 rounded-full font-bold hover:bg-gray-100 transition shadow-lg">
                            Shop Now
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($featuredProducts->count() > 0)
        <div class="mb-12">
            <div class="flex justify-between items-end border-b border-gray-200 pb-4 mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Featured Products</h2>
                <a href="{{ route('products.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">View All →</a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
                @foreach($featuredProducts as $product)
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-gray-100 transition-all duration-300 group flex flex-col h-full overflow-hidden">

                        <a href="#" class="block relative aspect-square bg-gray-50 p-4 flex items-center justify-center overflow-hidden">
                            @if($product->compare_price > $product->price)
                                <span class="absolute top-2 left-2 bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded-sm z-10">
                                    -{{ round((($product->compare_price - $product->price) / $product->compare_price) * 100) }}%
                                </span>
                            @endif

                            @if($product->primeImage)
                                <img src="{{ asset('storage/' . $product->primeImage->image_path) }}" alt="{{ $product->name }}" class="object-contain w-full h-full group-hover:scale-105 transition-transform duration-500">
                            @else
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            @endif
                        </a>

                        <div class="p-4 flex flex-col flex-1">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">{{ $product->category->name ?? 'Gear' }}</p>
                            <a href="#" class="text-sm font-semibold text-gray-900 hover:text-blue-600 line-clamp-2 mb-2 flex-1">
                                {{ $product->name }}
                            </a>

                            <div class="mt-auto pt-2 flex items-center justify-between">
                                <div>
                                    <span class="text-base font-bold text-red-600">${{ number_format($product->price, 2) }}</span>
                                    @if($product->compare_price > $product->price)
                                        <span class="text-xs text-gray-400 line-through ml-1">${{ number_format($product->compare_price, 2) }}</span>
                                    @endif
                                </div>

                                <button class="w-8 h-8 rounded-full bg-gray-50 hover:bg-black hover:text-white text-gray-900 flex items-center justify-center transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="mb-12 rounded-xl overflow-hidden bg-black text-white p-8 sm:p-12 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-sm">
            <div>
                <h3 class="text-2xl font-bold mb-2">Build Your Dream PC</h3>
                <p class="text-gray-400 max-w-lg">Get expert advice and premium components to assemble the ultimate workstation or gaming rig.</p>
            </div>
            <a href="#" class="whitespace-nowrap bg-white text-black px-6 py-3 rounded-md font-bold hover:bg-gray-200 transition">
                Contact Sales
            </a>
        </div>

        @if($newArrivals->count() > 0)
        <div class="mb-12">
            <div class="flex justify-between items-end border-b border-gray-200 pb-4 mb-6">
                <h2 class="text-2xl font-bold text-gray-900">New Arrivals</h2>
                <a href="{{ route('products.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">View All →</a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
                @foreach($newArrivals as $product)
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-gray-100 transition-all duration-300 group flex flex-col h-full overflow-hidden">

                        <a href="#" class="block relative aspect-square bg-gray-50 p-4 flex items-center justify-center overflow-hidden">
                            <span class="absolute top-2 right-2 bg-blue-600 text-white text-[10px] font-bold px-2 py-1 rounded-sm z-10">
                                NEW
                            </span>

                            @if($product->primeImage)
                                <img src="{{ asset('storage/' . $product->primeImage->image_path) }}" alt="{{ $product->name }}" class="object-contain w-full h-full group-hover:scale-105 transition-transform duration-500">
                            @else
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            @endif
                        </a>

                        <div class="p-4 flex flex-col flex-1">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">{{ $product->sku ?? 'NEW' }}</p>
                            <a href="#" class="text-sm font-semibold text-gray-900 hover:text-blue-600 line-clamp-2 mb-2 flex-1">
                                {{ $product->name }}
                            </a>

                            <div class="mt-auto pt-2 flex items-center justify-between">
                                <div>
                                    <span class="text-base font-bold text-red-600">${{ number_format($product->price, 2) }}</span>
                                </div>
                                <button class="w-8 h-8 rounded-full bg-gray-50 hover:bg-black hover:text-white text-gray-900 flex items-center justify-center transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
