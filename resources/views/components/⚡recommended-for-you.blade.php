<?php

use Livewire\Component;
use App\Models\Product;
use App\Services\UserAffinityService;
use Illuminate\Support\Collection;

new class extends Component {
    public Collection $recommendations;
    public bool $hasSignal = true;

    public function mount(UserAffinityService $affinityService): void
    {
        $customer = auth('customer')->user();

        if ($customer) {
            $this->recommendations = $affinityService->recommendedForUser($customer, 8);

            if ($this->recommendations->isEmpty()) {
                $this->hasSignal = false;
            }
        } else {
            $this->hasSignal = false;
            $this->recommendations = collect();
        }

        if (! $this->hasSignal) {
            $this->recommendations = $this->trendingFallback();
        }
    }

    /**
     * Trending products based on order volume in the past 14 days.
     *
     * @return Collection<int, Product>
     */
    private function trendingFallback(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->withCount(['orderItems as recent_sales' => function ($query): void {
                $query->whereHas('order', fn ($q) => $q->where('created_at', '>=', now()->subDays(14)));
            }])
            ->orderByDesc('recent_sales')
            ->with(['brand', 'category', 'primeImage'])
            ->limit(8)
            ->get();
    }
};
?>

@if($recommendations->isNotEmpty())
    <div class="mb-10 sm:mb-16">
        <div class="flex flex-wrap gap-2 justify-between items-end border-b border-gray-200 pb-3 sm:pb-4 mb-5 sm:mb-6">
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900">
                {{ auth('customer')->check() ? 'Recommended for You' : 'Trending Now' }}
            </h3>
            <a wire:navigate href="{{ route('products.index') }}" class="text-xs sm:text-sm font-semibold text-blue-600 hover:text-blue-800">View All &rarr;</a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-6">
            @foreach($recommendations as $product)
                <livewire:product-card :key="'rec-' . $product->id" :product="$product" context="recommendation" />
            @endforeach
        </div>
    </div>
@endif
