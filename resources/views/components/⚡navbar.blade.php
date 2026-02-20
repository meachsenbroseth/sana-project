<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    <div class="container mx-auto px-4 h-16 flex items-center justify-between gap-4">

        {{-- Logo --}}
        <a href="{{ route('home') }}" wire:navigate class="flex flex-shrink-0 hover:opacity-80 transition-opacity">
            <img src="{{ asset('images/logo.png') }}" alt="Phanna Computer" class="h-10 w-auto object-contain">
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
