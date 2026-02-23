<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\Brand; // Added Brand model

new class extends Component {
    public function with()
    {
        // Fetch top 4 categories
        $topCategories = Category::withCount('products')->orderByDesc('products_count')->take(4)->get();

        // Fetch brands for the carousel
        $brands = Brand::withCount('products')
            ->take(12) // Fetch up to 12 brands for the slider
            ->get();

        // Fetch featured products
        $featuredProducts = Product::with('primeImage')->where('is_active', true)->where('is_featured', true)->inRandomOrder()->take(10)->get();

        // Fetch newest products
        $newArrivals = Product::with('primeImage')->where('is_active', true)->latest()->take(10)->get();
        $siteSetting = SiteSetting::query()->first();
        $bannerImage = filled($siteSetting?->banner_image) ? asset('storage/' . $siteSetting->banner_image) : null;

        return [
            'topCategories' => $topCategories,
            'brands' => $brands,
            'featuredProducts' => $featuredProducts,
            'newArrivals' => $newArrivals,
            'bannerImage' => $bannerImage,
        ];
    }
};
?>

<div class="bg-[#faf9f6] min-h-screen pb-12">

    <style>
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <div
        class="w-full relative shadow-sm min-h-[400px] sm:min-h-[500px] lg:min-h-[650px] flex items-center mb-12 sm:mb-20">
        @if ($bannerImage)
            <img src="{{ $bannerImage }}" alt="Homepage banner" class="absolute inset-0 h-full w-full object-cover">
        @endif
        <div
            class="absolute inset-0 {{ $bannerImage ? 'bg-black/45' : 'bg-gradient-to-r from-blue-900 to-indigo-800' }}">
        </div>
{{--
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
        </div> --}}
    </div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        @if ($topCategories->count() > 0)
            <div class="mb-12 sm:mb-16">
                <div class="flex flex-wrap gap-4 justify-between items-center mb-6 sm:mb-8">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 uppercase tracking-wide">Shop Top Categories
                    </h2>
                    <a href="{{ route('products.index') }}"
                        class="px-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm font-semibold text-gray-900 hover:border-gray-900 transition-colors shadow-sm">
                        All Products →
                    </a>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    @foreach ($topCategories as $index => $category)
                        <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                            class="relative group rounded-2xl sm:rounded-[2rem] overflow-hidden aspect-[3/4] sm:aspect-[4/5] block bg-gray-900 shadow-sm">
                            @php
                                $placeholders = [
                                    'https://images.unsplash.com/photo-1595225476474-87563907a212?auto=format&fit=crop&q=80&w=600',
                                    'https://images.unsplash.com/photo-1615663245857-ac1e653815f7?auto=format&fit=crop&q=80&w=600',
                                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&q=80&w=600',
                                    'https://images.unsplash.com/photo-1600861194942-f883de0dfe96?auto=format&fit=crop&q=80&w=600',
                                ];
                                $bgImage =
                                    isset($category->image) && $category->image
                                        ? asset('storage/' . $category->image)
                                        : $placeholders[$index % 4];
                            @endphp
                            <img src="{{ $bgImage }}" alt="{{ $category->name }}"
                                class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-80 group-hover:opacity-100 transition-opacity duration-300">
                            </div>
                            <div
                                class="absolute bottom-0 left-0 right-0 p-6 text-center transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                                <h3 class="text-white font-bold text-lg sm:text-2xl mb-1">{{ $category->name }}</h3>
                                <p class="text-gray-300 text-xs sm:text-sm font-medium">{{ $category->products_count }}
                                    products</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($brands->count() > 0)
            <div class="mb-16 sm:mb-24 relative" x-data="{
                autoScroll: null,
                scrollNext() {
                    const slider = $refs.slider;
                    // Check if we reached the end of the scrollable area
                    if (slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 10) {
                        // Loop back to the start smoothly
                        slider.scrollTo({ left: 0, behavior: 'smooth' });
                    } else {
                        // Scroll right
                        slider.scrollBy({ left: 300, behavior: 'smooth' });
                    }
                },
                scrollPrev() {
                    $refs.slider.scrollBy({ left: -300, behavior: 'smooth' });
                },
                startAutoScroll() {
                    // Auto-scroll every 3 seconds (3000ms)
                    this.autoScroll = setInterval(() => this.scrollNext(), 3000);
                },
                stopAutoScroll() {
                    // Pause scrolling when hovering
                    clearInterval(this.autoScroll);
                }
            }" x-init="startAutoScroll()"
                @mouseenter="stopAutoScroll()" @mouseleave="startAutoScroll()">

                <div class="relative flex items-center group">

                    <button @click="scrollPrev"
                        class="absolute left-0 z-10 -ml-5 w-10 h-10 bg-white rounded-full shadow-md flex items-center justify-center border border-gray-100 text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-all opacity-0 group-hover:opacity-100 hidden sm:flex">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>
                    </button>

                    <div x-ref="slider"
                        class="flex overflow-x-auto gap-4 sm:gap-6 snap-x snap-mandatory hide-scrollbar w-full py-4 px-2 scroll-smooth">
                        @foreach ($brands as $brand)
                            <a href="{{ route('products.index', ['brand' => $brand->slug]) }}"
                                class="flex-none w-40 sm:w-48 h-24 sm:h-28 bg-white border border-gray-100 rounded-xl flex items-center justify-center snap-center shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                                @if (isset($brand->image) && $brand->image)
                                    <img src="{{ asset('storage/' . $brand->image) }}" alt="{{ $brand->name }}"
                                        class="max-w-[100px] max-h-[50px] object-contain opacity-60 hover:opacity-100 transition-opacity duration-300">
                                @else
                                    <span
                                        class="font-bold text-gray-400 uppercase tracking-widest text-sm hover:text-gray-900 transition-colors">{{ $brand->name }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    <button @click="scrollNext"
                        class="absolute right-0 z-10 -mr-5 w-10 h-10 bg-white rounded-full shadow-md flex items-center justify-center border border-gray-100 text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-all opacity-0 group-hover:opacity-100 hidden sm:flex">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>

                </div>
            </div>
        @endif

        @if ($featuredProducts->count() > 0)
            <div class="mb-10 sm:mb-16">
                <div
                    class="flex flex-wrap gap-2 justify-between items-end border-b border-gray-200 pb-3 sm:pb-4 mb-5 sm:mb-6">
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900">Featured Products</h3>
                    <a href="{{ route('products.index') }}"
                        class="text-xs sm:text-sm font-semibold text-blue-600 hover:text-blue-800">View All →</a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
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

                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
                    @foreach ($newArrivals as $product)
                        <livewire:product-card :key="'new-' . $product->id" :product="$product" />
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
