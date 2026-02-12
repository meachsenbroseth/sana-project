<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public int $cartCount = 0;

    public function mount()
    {
        $this->updateCartCount();
    }

    /**
     * This component listens for the signal from the Cart Page
     * or Add-to-Cart buttons, but DOES NOT re-dispatch it.
     */
    #[On('cart-updated')]
    public function updateCartCount()
    {
        $cart = session()->get('cart', []);

        $this->cartCount = collect($cart)->sum('quantity');

        // REMOVED: $this->dispatch('cart-updated');
        // Removing this prevents the component from triggering itself.
    }
};
?>

<div>
    <a href="{{ route('cart.index') }}" wire:navigate class="relative">
        <svg class="h-5 w-5 text-gray-700" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round">
            <circle cx="8" cy="21" r="1"></circle>
            <circle cx="19" cy="21" r="1"></circle>
            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
        </svg>
        @if ($cartCount > 0)
            <span
                class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                {{ $cartCount }}
            </span>
        @endif
    </a>
</div>
