<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    <header
        class="sticky top-0 z-50 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/60 border-b border-gray-200">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between gap-4">
            {{-- Logo --}}
            <a href="{{ route('home') }}" wire:navigate
                class="flex items-center gap-2 flex-shrink-0 hover:opacity-80 transition-opacity">
                <div class="w-8 h-8 bg-black rounded-md flex items-center justify-center">
                    <span class="text-white font-bold">P</span>
                </div>
                <span class="font-bold text-lg hidden sm:block">Phanna Computer</span>
            </a>

            {{-- Search Bar --}}
            <livewire:search-bar />

            <div class="flex items-center gap-4 flex-shrink-0">
                {{-- Actions --}}
                <livewire:cart-icon />
                {{-- User Auth --}} 
                <livewire:user-auth />
            </div>
        </div>
</div>
</header>
</div>
