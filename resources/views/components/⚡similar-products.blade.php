<?php

use Livewire\Component;
use App\Models\Product;
use App\Services\RecommendationService;
use Illuminate\Support\Collection;

new class extends Component {
    public Product $product;
    public Collection $similarProducts;

    public function mount(RecommendationService $recommendationService): void
    {
        $this->similarProducts = $recommendationService->similarByBehavior($this->product, 6);
    }
};
?>

<div>
    @if($similarProducts->isNotEmpty())
        <div class="mt-12 sm:mt-16 pt-8 border-t border-gray-200">
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">You might also like</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-6">
                @foreach($similarProducts as $similar)
                    <livewire:product-card :key="'similar-' . $similar->id" :product="$similar" context="recommendation" />
                @endforeach
            </div>
        </div>
    @endif
</div>
