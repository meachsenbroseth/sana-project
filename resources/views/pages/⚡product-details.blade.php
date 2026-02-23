<?php

use Livewire\Component;
use App\Models\Product;
use Illuminate\Support\Collection;

new class extends Component {
    public Product $product;
    public int $quantity = 1;
    public $selectedImage = null;
    public Collection $relatedProducts;

    public function mount($slug)
    {
        $this->product = Product::where('slug', $slug)->with('category', 'brand', 'images', 'approvedReviews.customer')->firstOrFail();

        // increment the views
        $this->product->incrementViews();

        // Set default selected image
        $this->selectedImage = $this->product->images->first()?->image_path;

        // related products
        $this->relatedProducts = Product::active()->where('category_id', $this->product->category_id)->whereKeyNot($this->product->id)->orderByDesc('is_featured')->inRandomOrder()->limit(4)->get();
    }
    public function selectImage(string $path)
    {
        $this->selectedImage = $path;
    }

    public function incrementQuantity()
    {
        if ($this->product->stock_quantity !== null && $this->quantity >= $this->product->stock_quantity) {
            return;
        }

        $this->quantity++;
    }
    public function decrementQuantity()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function addToCart()
    {
        // Check stock
        if ($this->product->stock_status !== 'in_stock') {
            session()->flash('error', 'This product is currently out of stock');
            return;
        }

        // Get cart from session
        $cart = session()->get('cart', []);

        $cartKey = 'product_' . $this->product->id;

        // Increase quantity if exists
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity']++;
        } else {
            // Add new item
            $cart[$cartKey] = [
                'product_id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'image' => $this->product->primeImage?->image_path,
                'quantity' => $this->quantity,
            ];
        }

        // Save cart
        session()->put('cart', $cart);

        // Refresh product state (important for Livewire)
        $this->product->refresh();

        // Update cart icon component
        $this->dispatch('cart-updated');

        // Flash message
        session()->flash('success', $this->product->name . ' has been added to your cart.');

        // Reset quantity (optional UX improvement)
        $this->quantity = 1;
    }
};
?>

<div class="py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm">
            <ol class="flex items-center gap-2">
                <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-blue-600">Home</a></li>
                <li class="text-gray-400">/</li>
                <li><a href="{{ route('products.index') }}" class="text-gray-500 hover:text-blue-600">Shop</a></li>
                <li class="text-gray-400">/</li>
                <li><a href="{{ route('products.index', ['category' => $product->category->slug]) }}"
                        class="text-gray-500 hover:text-blue-600">{{ $product->category->name }}</a></li>
                <li class="text-gray-400">/</li>
                <li class="text-gray-900 font-medium">{{ $product->name }}</li>
            </ol>
        </nav>

        <!-- Product Detail -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
            <div class="lg:grid lg:grid-cols-2 lg:gap-8 p-8">
                <!-- Images -->
                <div>
                    <!-- Main Image -->
                    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100 mb-4">
                        @if ($selectedImage)
                            <img src="{{ asset('storage/' . $selectedImage) }}" alt="{{ $product->name }}"
                                class="w-full h-full object-cover">
                        @else
                            <div
                                class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-300 to-gray-400">
                                <span class="text-9xl text-gray-500">{{ substr($product->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Thumbnail Images -->
                    @if ($product->images->count() > 1)
                        <div class="grid grid-cols-4 gap-4">
                            @foreach ($product->images as $image)
                                <button wire:click="selectImage('{{ $image->image_path }}')"
                                    class="aspect-square rounded-lg overflow-hidden border-2 {{ $selectedImage === $image->image_path ? 'border-blue-600' : 'border-gray-200' }} hover:border-indigo-400 transition">
                                    <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $product->name }}"
                                        class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Product Info -->
                <div>
                    <!-- Badges -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if ($product->status === 'new')
                            <span class="bg-green-100 text-green-800 text-sm font-semibold px-3 py-1 rounded">
                                New
                            </span>
                        @elseif ($product->status === 'used')
                            <span class="bg-orange-100 text-orange-800 text-sm font-semibold px-3 py-1 rounded">
                                Used
                            </span>
                        @endif
                        @if ($product->is_featured)
                            <span class="bg-yellow-100 text-yellow-800 text-sm font-semibold px-3 py-1 rounded">
                                Featured
                            </span>
                        @endif
                        @if ($product->stock_status === 'in_stock')
                            <span class="bg-green-100 text-green-800 text-sm font-semibold px-3 py-1 rounded">
                                In Stock
                            </span>
                        @elseif ($product->stock_status === 'pre_order')
                            <span class=" bg-yellow-100 text-yellow-600 text-sm font-semibold px-3 py-1 rounded">
                                Pre Order
                            </span>
                        @else
                            <span class="bg-red-100 text-red-800 text-sm font-semibold px-3 py-1 rounded">
                                Out of Stock
                            </span>
                        @endif
                    </div>

                    <!-- Brand -->
                    @if ($product->brand)
                        <p class="text-sm text-gray-500 mb-2">{{ $product->brand->name }}</p>
                    @endif

                    <!-- Title -->
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>

                    <!-- Rating -->
                    @if ($product->reviews_count > 0)
                        <div class="flex items-center gap-2 mb-4">
                            <div class="flex text-yellow-400">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= floor($product->average_rating))
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                            <path
                                                d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 fill-current text-gray-300" viewBox="0 0 20 20">
                                            <path
                                                d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                        </svg>
                                    @endif
                                @endfor
                            </div>
                            <span class="text-gray-600">{{ number_format($product->average_rating, 1) }}
                                ({{ $product->reviews_count }} reviews)</span>
                        </div>
                    @endif

                    <!-- Price -->
                    <div class="mb-6">

                        <div class="flex items-center gap-3">
                            <span
                                class="text-3xl font-bold text-gray-900">${{ number_format($product->price, 2) }}</span>
                            @if ($product->compare_price)
                                <span
                                    class="text-xl text-gray-500 line-through">${{ number_format($product->compare_price, 2) }}</span>
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm font-semibold">
                                    -{{ $product->discount_percentage }}%
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Description -->
                    @if ($product->description)
                        <div x-data="{ expanded: false }">
                            <div class="prose prose-sm max-w-none" x-show="!expanded" x-cloak>
                                {!! Str::markdown(Str::limit($product->description, 200)) !!}
                                @if (strlen($product->description) > 200)
                                    <button @click="expanded = true"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Read more...
                                    </button>
                                @endif
                            </div>

                            <div class="prose prose-sm max-w-none" x-show="expanded" x-cloak>
                                {!! Str::markdown($product->description) !!}
                                <button @click="expanded = false"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Show less
                                </button>
                            </div>
                        </div>
                    @endif



                    <!-- Quantity -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-900 mb-3">Quantity:</label>
                        <div class="flex items-center gap-3">
                            <button wire:click="decrementQuantity"
                                class="w-10 h-10 rounded-lg border border-gray-300 hover:bg-gray-100 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 12H4" />
                                </svg>
                            </button>
                            <input type="number" wire:model="quantity" min="1"
                                class="w-20 text-center border border-gray-300 rounded-lg py-2">
                            <button wire:click="incrementQuantity"
                                class="w-10 h-10 rounded-lg border border-gray-300 hover:bg-gray-100 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    @if (session()->has('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Add to Cart -->
                    @if ($product->stock_status === 'in_stock')
                        <button wire:click="addToCart" wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed" wire:target="addToCart"
                            class="w-full cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">

                            <!-- Cart Icon -->
                            <svg wire:loading.remove wire:target="addToCart" class="w-5 h-5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>

                            <!-- Spinner -->
                            <svg wire:loading wire:target="addToCart" class="w-5 h-5 animate-spin" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>

                            <span wire:loading.remove wire:target="addToCart">
                                Add to Cart
                            </span>

                            <span wire:loading wire:target="addToCart">
                                Adding...
                            </span>
                        </button>
                    @elseif ($product->stock_status === 'pre_order')
                        <button wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart"
                            class="w-full cursor-pointer bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Pre-order Now
                        </button>
                    @else
                        <button disabled
                            class="w-full bg-gray-300 text-gray-500 font-medium py-3 px-4 rounded-lg cursor-not-allowed flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Out of Stock
                        </button>
                    @endif

                    <!-- Product Details -->
                    <div class="mt-8 border-t pt-6 space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">SKU:</span>
                            <span class="font-medium">{{ $product->sku }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Category:</span>
                            <a href="{{ route('products.index', ['category' => $product->category->slug]) }}"
                                class="font-medium text-blue-600 hover:text-indigo-700">
                                {{ $product->category->name }}
                            </a>
                        </div>
                        @if ($product->brand)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Brand:</span>
                                <a href="{{ route('products.index', ['brand' => $product->brand->slug]) }}"
                                    class="font-medium text-blue-600 hover:text-indigo-700">
                                    {{ $product->brand->name }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs: Reviews -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8" x-data="{ activeTab: 'reviews' }">

            <!-- Tab Header -->
            <div class="border-b">
                <nav class="flex">
                    <button @click="activeTab = 'reviews'"
                        :class="activeTab === 'reviews'
                            ?
                            'border-blue-600 text-blue-600' :
                            'border-transparent text-gray-600'"
                        class="px-6 py-4 border-b-2 font-medium transition">
                        Reviews ({{ $product->reviews_count }})
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-8">
                <div x-show="activeTab === 'reviews'" x-cloak>
                    @if ($product->approvedReviews->count() > 0)
                        <div class="space-y-6">
                            @foreach ($product->approvedReviews as $review)
                                <div class="border-b pb-6 last:border-b-0">
                                    <div class="flex items-start gap-4">
                                        <!-- Avatar -->
                                        <div class="flex-shrink-0">
                                            <div
                                                class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">
                                                {{ strtoupper(substr($review->customer->name, 0, 1)) }}
                                            </div>
                                        </div>

                                        <!-- Review Content -->
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <h4 class="font-semibold">{{ $review->customer->name }}</h4>

                                                @if ($review->is_verified_purchase)
                                                    <span
                                                        class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                                                        Verified Purchase
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Rating -->
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="flex text-yellow-400">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <svg class="w-4 h-4 {{ $i <= $review->rating ? 'fill-current' : 'fill-current text-gray-300' }}"
                                                            viewBox="0 0 20 20">
                                                            <path
                                                                d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                                        </svg>
                                                    @endfor
                                                </div>

                                                <span class="text-sm text-gray-500">
                                                    {{ $review->created_at->diffForHumans() }}
                                                </span>
                                            </div>

                                            @if ($review->title)
                                                <h5 class="font-medium mb-1">{{ $review->title }}</h5>
                                            @endif

                                            @if ($review->comment)
                                                <p class="text-gray-700">{{ $review->comment }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500">
                                No reviews yet. Be the first to review this product!
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Related Products -->
        @if ($this->relatedProducts->count() > 0)
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Products</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($this->relatedProducts as $relatedProduct)
                        <livewire:product-card :product="$relatedProduct" :key="'related-' . $relatedProduct->id" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
