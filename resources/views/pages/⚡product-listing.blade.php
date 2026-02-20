<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;

new class extends Component {
    use WithPagination;

    #[Url]
    public string $category = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $brand = '';

    #[Url]
    public int $minPrice = 0;

    #[Url]
    public int $maxPrice = 0;

    #[Url]
    public string $sort = 'newest';

    #[Url]
    public bool $featured = false;

    public array $priceRange = [0, 10000];

    public function mount(): void
    {
        $max = Product::active()->max('price') ?? 10000;
        $this->priceRange = [0, (int) ceil($max)];

        if ($this->maxPrice === 0) {
            $this->maxPrice = $this->priceRange[1];
        }
    }

    #[Computed]
    public function products()
    {
        return Product::active()
            ->when($this->category, fn($q) => $q->whereHas('category', fn($c) => $c->where('slug', $this->category)))
            ->when($this->brand, fn($q) => $q->whereHas('brand', fn($b) => $b->where('slug', $this->brand)))
            ->when($this->featured, fn($q) => $q->where('is_featured', true))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->whereBetween('price', [$this->minPrice, $this->maxPrice])
            ->when($this->sort === 'price_low', fn($q) => $q->orderBy('price'))
            ->when($this->sort === 'price_high', fn($q) => $q->orderByDesc('price'))
            ->when($this->sort === 'newest', fn($q) => $q->latest())
            ->when($this->sort === 'name_asc', fn($q) => $q->orderBy('name'))
            ->when($this->sort === 'name_desc', fn($q) => $q->orderByDesc('name'))
            ->paginate(9);
    }

    #[Computed]
    public function categories()
    {
        return Category::withCount('products')->orderBy('name')->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::withCount('products')->orderBy('name')->get();
    }

    public function applyPriceFilter(): void
    {
        if ($this->minPrice < 0) {
            $this->minPrice = 0;
        }
        if ($this->maxPrice < $this->minPrice) {
            $this->maxPrice = $this->minPrice;
        }
        if ($this->maxPrice > $this->priceRange[1]) {
            $this->maxPrice = $this->priceRange[1];
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['category', 'search', 'brand', 'featured']);
        $this->minPrice = 0;
        $this->maxPrice = $this->priceRange[1];
        $this->sort = 'newest';
        $this->resetPage();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedCategory(): void { $this->resetPage(); }
    public function updatedBrand(): void { $this->resetPage(); }
    public function updatedSort(): void { $this->resetPage(); }
    public function updatedFeatured(): void { $this->resetPage(); }

    public function formatPrice($price): string
    {
        return '$' . number_format($price, 2);
    }
};
?>

<div>
    <style>
        .dual-range input[type=range] {
            -webkit-appearance: none;
            appearance: none;
            pointer-events: none;
            background: transparent;
        }
        .dual-range input[type=range]::-webkit-slider-thumb {
            pointer-events: auto;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            background-color: white;
            border: 2px solid #4f46e5;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .dual-range input[type=range]::-moz-range-thumb {
            pointer-events: auto;
            width: 18px;
            height: 18px;
            background-color: white;
            border: 2px solid #4f46e5;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
    </style>

    <div class="bg-gray-50 py-8 min-h-screen">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav class="mb-6 text-sm">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-blue-600">Home</a></li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-900 font-medium">Shop</li>
                </ol>
            </nav>

            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    @if ($category)
                        {{ \App\Models\Category::where('slug', $category)->first()?->name ?? 'Category' }}
                    @elseif($brand)
                        {{ \App\Models\Brand::where('slug', $brand)->first()?->name ?? 'Brand' }}
                    @elseif($search)
                        Search Results for "{{ $search }}"
                    @else
                        All Products
                    @endif
                </h1>
                <p class="text-gray-600">Showing {{ $this->products->total() }} products</p>
            </div>

            <div class="lg:grid lg:grid-cols-4 lg:gap-8">
                <aside class="hidden lg:block">
                    <div class="sticky top-24 space-y-6">

                        @if ($search || $category || $brand || $featured || $minPrice > 0 || $maxPrice < $priceRange[1])
                            <div class="bg-white p-4 rounded-lg shadow-sm">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-semibold text-gray-900">Active Filters</h3>
                                    <button wire:click="clearFilters" class="text-sm text-blue-600 hover:text-indigo-700">Clear All</button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if ($category)
                                        <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm">
                                            Category: {{ \App\Models\Category::where('slug', $category)->first()?->name }}
                                            <button wire:click="$set('category', '')" class="hover:text-indigo-900">×</button>
                                        </span>
                                    @endif
                                    @if ($brand)
                                        <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm">
                                            Brand: {{ \App\Models\Brand::where('slug', $brand)->first()?->name }}
                                            <button wire:click="$set('brand', '')" class="hover:text-indigo-900">×</button>
                                        </span>
                                    @endif
                                    @if ($featured)
                                        <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm">
                                            Featured
                                            <button wire:click="$set('featured', false)" class="hover:text-indigo-900">×</button>
                                        </span>
                                    @endif
                                    @if ($minPrice > 0 || $maxPrice < $priceRange[1])
                                        <span class="inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm">
                                            Price: {{ $this->formatPrice($minPrice) }} - {{ $this->formatPrice($maxPrice) }}
                                            <button wire:click="$set(['minPrice' => 0, 'maxPrice' => $priceRange[1]])" class="hover:text-indigo-900">×</button>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <h3 class="font-semibold text-gray-900 mb-3">Categories</h3>
                            <ul class="space-y-2 max-h-80 overflow-y-auto pr-2">
                                <li>
                                    <button wire:click="$set('category', '')"
                                        class="w-full text-left px-3 py-2 rounded {{ !$category ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                        All Categories
                                    </button>
                                </li>
                                @foreach ($this->categories as $cat)
                                    <li>
                                        <button wire:click="$set('category', '{{ $cat->slug }}')"
                                            class="w-full text-left px-3 py-2 rounded {{ $category === $cat->slug ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                            <div class="flex justify-between items-center">
                                                <span>{{ $cat->name }}</span>
                                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">{{ $cat->products_count }}</span>
                                            </div>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <h3 class="font-semibold text-gray-900 mb-3">Brands</h3>
                            <ul class="space-y-2 max-h-64 overflow-y-auto pr-2">
                                <li>
                                    <button wire:click="$set('brand', '')"
                                        class="w-full text-left px-3 py-2 rounded {{ !$brand ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                        All Brands
                                    </button>
                                </li>
                                @foreach ($this->brands as $br)
                                    <li>
                                        <button wire:click="$set('brand', '{{ $br->slug }}')"
                                            class="w-full text-left px-3 py-2 rounded {{ $brand === $br->slug ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                                            <div class="flex justify-between items-center">
                                                <span>{{ $br->name }}</span>
                                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">{{ $br->products_count }}</span>
                                            </div>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="bg-white p-4 rounded-lg shadow-sm"
                             x-data="{
                                minPrice: @entangle('minPrice'),
                                maxPrice: @entangle('maxPrice'),
                                maxLimit: {{ $priceRange[1] > 0 ? $priceRange[1] : 10000 }}
                             }">

                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-semibold text-gray-900">Price Range</h3>
                            </div>

                            <div class="relative w-full h-2 mb-8 dual-range">
                                <div class="absolute top-0 bottom-0 left-0 right-0 bg-gray-200 rounded-full z-10"></div>

                                <div class="absolute top-0 bottom-0 bg-indigo-600 rounded-full z-20"
                                     x-bind:style="`left: ${(minPrice / maxLimit) * 100}%; right: ${100 - (maxPrice / maxLimit) * 100}%;`">
                                </div>

                                <input type="range" min="0" x-bind:max="maxLimit"
                                       x-model="minPrice"
                                       @input="minPrice = Math.min(minPrice, maxPrice - 1)"
                                       @change="$wire.set('minPrice', minPrice)"
                                       class="absolute top-0 bottom-0 left-0 right-0 w-full h-2 z-30 m-0 p-0">

                                <input type="range" min="0" x-bind:max="maxLimit"
                                       x-model="maxPrice"
                                       @input="maxPrice = Math.max(maxPrice, minPrice + 1)"
                                       @change="$wire.set('maxPrice', maxPrice)"
                                       class="absolute top-0 bottom-0 left-0 right-0 w-full h-2 z-30 m-0 p-0">
                            </div>

                            <div class="flex items-center gap-2">
                                <div class="relative w-full">
                                    <span class="absolute left-3 top-2.5 text-gray-500">$</span>
                                    <input type="number" x-model="minPrice" @change="$wire.set('minPrice', minPrice)" min="0" x-bind:max="maxLimit"
                                        class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                                </div>
                                <span class="text-gray-500">to</span>
                                <div class="relative w-full">
                                    <span class="absolute left-3 top-2.5 text-gray-500">$</span>
                                    <input type="number" x-model="maxPrice" @change="$wire.set('maxPrice', maxPrice)" min="0" x-bind:max="maxLimit"
                                        class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="featured"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-3 text-gray-700 font-medium">Featured Products Only</span>
                            </label>
                        </div>
                    </div>
                </aside>

                <div class="lg:col-span-3">
                    <div class="lg:hidden mb-4">
                        <button onclick="document.getElementById('mobile-filters').classList.toggle('hidden')"
                            class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2 flex items-center justify-between shadow-sm">
                            <span class="font-medium">Filters & Categories</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="text-sm text-gray-600">
                                Showing {{ $this->products->firstItem() ?? 0 }} - {{ $this->products->lastItem() ?? 0 }}
                                of {{ $this->products->total() }} products
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-700 font-medium">Sort by:</span>
                                <select wire:model.live="sort" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    <option value="newest">Newest</option>
                                    <option value="price_low">Price: Low to High</option>
                                    <option value="price_high">Price: High to Low</option>
                                    <option value="name_asc">Name: A-Z</option>
                                    <option value="name_desc">Name: Z-A</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    @if ($this->products->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($this->products as $product)
                                <livewire:product-card :key="$product->id" :product="$product" />
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $this->products->links() }}
                        </div>
                    @else
                        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No products found</h3>
                            <p class="text-gray-600 mb-6">Try adjusting your filters or search term</p>
                            <button wire:click="clearFilters" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                Clear All Filters
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div id="mobile-filters" class="fixed inset-0 z-50 lg:hidden hidden">
            <div class="absolute inset-0 bg-black bg-opacity-50" onclick="document.getElementById('mobile-filters').classList.add('hidden')"></div>

            <div class="absolute right-0 top-0 bottom-0 w-full max-w-sm bg-gray-50 overflow-y-auto">
                <div class="sticky top-0 bg-white border-b p-4 flex items-center justify-between z-10">
                    <h3 class="text-lg font-semibold">Filters</h3>
                    <button onclick="document.getElementById('mobile-filters').classList.add('hidden')" class="text-gray-500 hover:text-gray-700 bg-gray-100 rounded-full p-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-4 space-y-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-gray-900 mb-3">Categories</h3>
                        <select wire:model.live="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Categories</option>
                            @foreach ($this->categories as $cat)
                                <option value="{{ $cat->slug }}">{{ $cat->name }} ({{ $cat->products_count }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-gray-900 mb-3">Brands</h3>
                        <select wire:model.live="brand" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Brands</option>
                            @foreach ($this->brands as $br)
                                <option value="{{ $br->slug }}">{{ $br->name }} ({{ $br->products_count }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-gray-900">Price Range</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="number" wire:model.live.debounce.500ms="minPrice" placeholder="Min" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <span class="text-gray-500">-</span>
                            <input type="number" wire:model.live.debounce.500ms="maxPrice" placeholder="Max" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="featured" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5">
                            <span class="ml-3 text-gray-700 font-medium">Featured Products Only</span>
                        </label>
                    </div>

                    <button wire:click="clearFilters" onclick="document.getElementById('mobile-filters').classList.add('hidden')" class="w-full bg-gray-200 text-gray-800 py-3 rounded-lg font-bold hover:bg-gray-300 transition">
                        Clear All Filters
                    </button>
                    <button onclick="document.getElementById('mobile-filters').classList.add('hidden')" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition">
                        Show Results
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
