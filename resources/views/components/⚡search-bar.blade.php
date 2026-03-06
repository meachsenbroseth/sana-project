<?php

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $searchQuery = '';

    #[Computed]
    public function suggestedProducts(): Collection
    {
        $searchTerm = trim($this->searchQuery);

        if (mb_strlen($searchTerm) < 2) {
            return new Collection();
        }

        return Product::search($searchTerm)
            ->query(function (Builder $query): void {
                $query->active()->with(['brand']);
            })
            ->take(5)
            ->get();
    }

    public function submitSearch(): void
    {
        $searchTerm = trim($this->searchQuery);

        if ($searchTerm !== '') {
            $this->redirectRoute('products.index', ['search' => $searchTerm], navigate: true);
        }
    }
};
?>

<div x-data="{ searchOpen: false }" class="w-full flex justify-end sm:justify-center">

    <button type="button" @click="searchOpen = !searchOpen" class="sm:hidden p-2 text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
    </button>

    <div :class="searchOpen ? 'fixed top-16 left-0 w-full px-4 py-3 bg-white shadow-md z-50 border-b border-gray-100' : 'hidden sm:block sm:relative sm:w-full'" x-cloak>
        <form wire:submit="submitSearch" class="relative w-full">
            <flux:input
                type="text"
                wire:model.live.debounce.300ms="searchQuery"
                icon="magnifying-glass"
                placeholder="Search products..."
                autocomplete="off"
                class="pe-24"
            />

            @if (trim($searchQuery) !== '')
                <flux:button type="submit" variant="primary" class="absolute right-1 top-1/2 -translate-y-1/2 h-8">
                    Search
                </flux:button>
            @endif

            @if (mb_strlen(trim($searchQuery)) >= 2)
                <div class="absolute mt-2 w-full rounded-lg border border-gray-200 bg-white shadow-lg z-50 overflow-hidden">
                    @forelse ($this->suggestedProducts as $product)
                        <a href="{{ route('products.show', $product->slug) }}"
                            wire:navigate
                            class="block border-b border-gray-100 last:border-b-0 px-3 py-2 hover:bg-gray-50 transition-colors">
                            <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                            <p class="text-xs text-gray-500">{{ $product->brand?->name ?? 'Unknown brand' }}</p>
                        </a>
                    @empty
                        <p class="px-3 py-2 text-sm text-gray-500">No products found.</p>
                    @endforelse
                </div>
            @endif
        </form>
    </div>

    <div x-show="searchOpen" x-transition.opacity @click="searchOpen = false" class="fixed inset-0 top-16 bg-black/20 z-40 sm:hidden" x-cloak></div>
</div>
