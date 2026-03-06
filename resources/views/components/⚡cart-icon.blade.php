<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public int $cartCount = 0;
    public bool $floating = false;

    public function mount(): void
    {
        $this->updateCartCount();
    }

    /**
     * This component listens for the signal from the Cart Page
     * or Add-to-Cart buttons, but DOES NOT re-dispatch it.
     */
    #[On('cart-updated')]
    public function updateCartCount(): void
    {
        $cart = session()->get('cart', []);

        $this->cartCount = collect($cart)->sum('quantity');

        // REMOVED: $this->dispatch('cart-updated');
        // Removing this prevents the component from triggering itself.
    }
};
?>

<div>
    <a wire:navigate href="{{ route('cart.index') }}"
        class="{{ $floating ? 'group relative flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg shadow-blue-600/30 transition hover:scale-105 hover:bg-blue-700' : 'relative' }}">
        <svg class="{{ $floating ? 'h-6 w-6 text-white' : 'h-5 w-5 text-gray-700' }}" xmlns="http://www.w3.org/2000/svg"
            width="24" height="24"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round">
            <circle cx="8" cy="21" r="1"></circle>
            <circle cx="19" cy="21" r="1"></circle>
            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
        </svg>
        <span class="sr-only">Go to cart</span>
        @if ($cartCount > 0)
            <span
                class="{{ $floating ? 'absolute -right-1 -top-1 flex h-6 min-w-6 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white' : 'absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white' }}">
                {{ $cartCount }}
            </span>
        @endif
    </a>
</div>
