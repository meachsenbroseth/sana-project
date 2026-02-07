<?php

use Livewire\Component;

new class extends Component {
    public int $cartCount = 0;

    public function mount()
    {
        $this->updateCartCount();
    }

    #[On('cart-updated')]
    public function updateCartCount()
    {
        $cart = session()->get('cart', []);

        $this->cartCount = collect($cart)->sum('quantity');
    }
};
?>

<div>
    <a href="/cart" wire:navigate
       class="relative p-2 rounded-md hover:bg-gray-100 transition-colors">
        
        <svg class="h-5 w-5 text-gray-700"
             xmlns="http://www.w3.org/2000/svg"
             fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="2">
            <circle cx="8" cy="21" r="1" />
            <circle cx="19" cy="21" r="1" />
            <path d="M2.05 2.05h2l2.66 12.42
                     a2 2 0 0 0 2 1.58h9.78
                     a2 2 0 0 0 1.95-1.57
                     l1.65-7.43H5.12" />
        </svg>

        @if ($cartCount > 0)
            <span
                class="absolute -top-1 -right-1
                       h-5 w-5 flex items-center justify-center
                       text-xs bg-red-600 text-white rounded-full">
                {{ $cartCount }}
            </span>
        @endif
    </a>
</div>

