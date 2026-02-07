<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="flex relative max-w-2xl flex-1">
    {{-- Simplicity is the ultimate sophistication. - Leonardo da Vinci --}}
    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400"
        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>
    <input type="text" placeholder="Search products..." class="pl-10 w-full h-10 rounded-md border border-gray-300 bg-gray-50 focus:bg-white  focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none px-3 text-sm  transition-colors">
</div>
