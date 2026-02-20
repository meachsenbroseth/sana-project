<?php

use Livewire\Component;

new class extends Component {
    public string $searchQuery = '';

    public function submitSearch()
    {
        if (!empty(trim($this->searchQuery))) {
            $this->redirectRoute('products.index', ['search' => $this->searchQuery], navigate: true);
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
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>

            <input type="text"
                wire:model="searchQuery"
                placeholder="Search products..."
                class="pl-10 w-full h-10 rounded-md border border-gray-300 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-black focus:border-black focus:outline-none px-3 text-sm transition-colors"
                autocomplete="off"
                required
            >

            @if($searchQuery)
                <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black text-white p-1.5 rounded text-xs font-bold hover:bg-gray-800 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            @endif
        </form>
    </div>

    <div x-show="searchOpen" x-transition.opacity @click="searchOpen = false" class="fixed inset-0 top-16 bg-black/20 z-40 sm:hidden" x-cloak></div>
</div>
