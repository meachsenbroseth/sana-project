<?php

use Livewire\Component;
use App\Models\Product;

new class extends Component {
    public Product $product;
    public function addToCart() {}
    // Helper method to format price
    private function formatPrice($price): string
    {
        return '$' . number_format($price, 2);
    }
};
?>
<div>
    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">
        <!-- Product Image -->
        <div class="relative aspect-square overflow-hidden bg-gray-100">
            @if ($product->primaryImage)
                <img src="{{ asset('storage/' . $product->primaryImage->image_path) }}"
                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif
            @if ($product->is_featured)
                <span class="absolute top-2 left-2 bg-yellow-500 text-white text-xs font-semibold px-2 py-1 rounded">
                    Featured
                </span>
            @endif
        </div>

        <!-- Product Info -->
        <div class="p-4">
            <div class="mb-2">
                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                    {{ $product->category->name ?? 'Uncategorized' }}
                </span>
            </div>
            <h3 class="font-semibold text-gray-900 mb-1 line-clamp-1">{{ $product->name }}</h3>
            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $product->short_description }}</p>

            <!-- Price and Rating -->
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-lg font-bold text-gray-900">{{ $this->formatPrice($product->price) }}</span>
                    @if ($product->compare_price > $product->price)
                        <span
                            class="ml-2 text-sm text-gray-500 line-through">{{ $this->formatPrice($product->compare_price) }}</span>
                    @endif
                </div>
                @if ($product->reviews_count > 0)
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span class="ml-1 text-sm text-gray-600">{{ number_format($product->reviews_count, 1) }}</span>
                    </div>
                @endif
            </div>

            <!-- Actions -->

            <div class="mt-4 flex gap-2">
                @if ($product->stock_status === 'in_stock')
                    <button wire:click="addToCart" wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <svg wire:loading class="w-4 h-4 animate-spin" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span wire:loading.remove>Add to Cart</span>
                        <span wire:loading>Adding...</span>
                    </button>
                @elseif ($product->stock_status === 'pre_order')
                    <button wire:click="addToCart" wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Pre-order Now
                    </button>
                @else
                    <!-- Out of Stock -->
                    <button disabled
                        class="flex-1 bg-gray-300 text-gray-500 font-medium py-2.5 px-4 rounded-lg cursor-not-allowed flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        Out of Stock
                    </button>
                @endif
                <a href="{{ route('products.show', $product->slug) }}"
                    class="flex-1 border border-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-50 transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View
                </a>
            </div>
        </div>
    </div>
</div>
