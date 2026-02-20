<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    <div class="container mx-auto px-4 h-16 flex items-center justify-between gap-4">

        {{-- Logo --}}
        <div class="flex-shrink-0 flex items-center lg:w-1/4">
            <a href="{{ route('home') }}" wire:navigate class="flex flex-shrink-0 hover:opacity-80 transition-opacity">
                <img src="{{ asset('images/logo.png') }}" alt="Phanna Computer"
                    class="w-28 h-auto sm:w-auto sm:h-10 object-contain">
            </a>
        </div>


        {{-- Search Bar --}}
        <div class="flex-1 flex justify-end sm:justify-center lg:w-2/4 max-w-2xl mx-auto">
            <div class="w-full">
                <livewire:search-bar />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 sm:gap-4 flex-shrink-0 lg:w-1/4">
            {{-- Actions --}}
            <livewire:cart-icon />

            {{-- User Auth --}}
            <livewire:user-auth />
        </div>

    </div>
</div>
