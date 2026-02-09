<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Brand;

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
        return \App\Models\Category::withCount('products')->orderBy('name')->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::withCount('products')->orderBy('name')->get();
    }

    public function applyPriceFilter(): void
    {
        // Validate price range
        if ($this->minPrice < 0) $this->minPrice = 0;
        if ($this->maxPrice < $this->minPrice) $this->maxPrice = $this->minPrice;
        if ($this->maxPrice > $this->priceRange[1]) $this->maxPrice = $this->priceRange[1];
        
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedBrand(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedFeatured(): void
    {
        $this->resetPage();
    }

    // Helper method to format price
    private function formatPrice($price): string
    {
        return '$' . number_format($price, 2);
    }
};
?>

<div class="bg-gray-50 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-6 text-sm">
            <ol class="flex items-center gap-2">
                <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-blue-600">Home</a></li>
                <li class="text-gray-400">/</li>
                <li class="text-gray-900 font-medium">Shop</li>
            </ol>
        </nav>

        <!-- Header -->
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
            <!-- Filters Sidebar -->
            <aside class="hidden lg:block">
                <div class="sticky top-24 space-y-6">
                    <!-- Active Filters -->
                    @if ($search || $category || $brand || $featured || $minPrice > 0 || $maxPrice < $priceRange[1])
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-semibold text-gray-900">Active Filters</h3>
                                <button wire:click="clearFilters" class="text-sm text-blue-600 hover:text-indigo-700">
                                    Clear All
                                </button>
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

                    <!-- Categories -->
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-gray-900 mb-3">Categories</h3>
                        <ul class="space-y-2 max-h-80 overflow-y-auto">
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
                                            <span class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded-full">{{ $cat->products_count }}</span>
                                        </div>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Brands -->
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <h3 class="font-semibold text-gray-900 mb-3">Brands</h3>
                        <ul class="space-y-2 max-h-64 overflow-y-auto">
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
                                            <span class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded-full">{{ $br->products_count }}</span>
                                        </div>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Price Range -->
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="font-semibold text-gray-900">Price Range</h3>
                            <span class="text-sm text-gray-600">{{ $this->formatPrice($minPrice) }} - {{ $this->formatPrice($maxPrice) }}</span>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <input type="number" wire:model.live.debounce.500ms="minPrice" 
                                    placeholder="Min" min="0" max="{{ $priceRange[1] }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <span class="text-gray-500">to</span>
                                <input type="number" wire:model.live.debounce.500ms="maxPrice" 
                                    placeholder="Max" min="0" max="{{ $priceRange[1] }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Featured Filter -->
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="featured" 
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-3 text-gray-700 font-medium">Featured Products Only</span>
                        </label>
                    </div>
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="lg:col-span-3">
                <!-- Mobile Filters Toggle -->
                <div class="lg:hidden mb-4">
                    <button onclick="document.getElementById('mobile-filters').classList.toggle('hidden')"
                        class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2 flex items-center justify-between">
                        <span class="font-medium">Filters</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </button>
                </div>

                <!-- Sort and Results Info -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Showing {{ $this->products->firstItem() ?? 0 }} - {{ $this->products->lastItem() ?? 0 }} of {{ $this->products->total() }} products
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-700 font-medium">Sort by:</span>
                            <select wire:model.live="sort" 
                                class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="newest">Newest</option>
                                <option value="price_low">Price: Low to High</option>
                                <option value="price_high">Price: High to Low</option>
                                <option value="name_asc">Name: A-Z</option>
                                <option value="name_desc">Name: Z-A</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                @if($this->products->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($this->products as $product)
                            <livewire:product-card :key="$product->id" :product="$product" />
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $this->products->links() }}
                    </div>
                @else
                    <!-- No Results -->
                    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No products found</h3>
                        <p class="text-gray-600 mb-6">Try adjusting your filters or search term</p>
                        <button wire:click="clearFilters"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            Clear All Filters
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Mobile Filters Panel -->
    <div id="mobile-filters" class="fixed inset-0 z-50 lg:hidden hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50" onclick="document.getElementById('mobile-filters').classList.add('hidden')"></div>
        <div class="absolute right-0 top-0 bottom-0 w-full max-w-sm bg-white overflow-y-auto">
            <div class="sticky top-0 bg-white border-b p-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold">Filters</h3>
                <button onclick="document.getElementById('mobile-filters').classList.add('hidden')" 
                        class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-4 space-y-6">
                <!-- Include all filter sections from sidebar here -->
                <!-- Search, Categories, Brands, Price Range, Featured -->
            </div>
        </div>
    </div>
</div>