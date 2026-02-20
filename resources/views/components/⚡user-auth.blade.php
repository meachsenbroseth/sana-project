<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    @auth('customer')
        @php($customer = auth('customer')->user())

        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
            <button @click="open = !open" class="flex items-center gap-2 p-2 rounded-md hover:bg-gray-100 transition-colors">

                @if ($customer->avatar)
                    <img src="{{ $customer->avatar }}" class="h-8 w-8 rounded-full object-cover" alt="{{ $customer->name }}">
                @else
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-blue-600 font-medium text-sm">
                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                        </span>
                    </div>
                @endif

                <span class="hidden sm:inline text-sm font-medium text-gray-900">
                    {{ Str::limit($customer->name, 15) }}
                </span>

                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </button>

            <div x-show="open" x-transition x-cloak
                class="absolute right-0 mt-2 w-56 z-50 bg-white shadow-lg rounded-lg p-2 border border-gray-100">

                <div class="px-3 py-3 border-b border-gray-100 mb-2">
                    <p class="font-medium text-gray-900 truncate">{{ $customer->name }}</p>
                    <p class="text-xs text-gray-500 truncate mt-0.5">{{ $customer->email }}</p>
                </div>

                <a href="{{ route('customer.dashboard') }}" wire:navigate
                    class="flex items-center gap-3 px-3 py-2.5 text-sm rounded-lg hover:bg-gray-50 text-gray-700">
                    My Account
                </a>

                <a href="{{ route('customer.profile') }}" wire:navigate
                    class="flex items-center gap-3 px-3 py-2.5 text-sm rounded-lg hover:bg-gray-50 text-gray-700">
                    Profile
                </a>

                <a href="{{ route('customer.orders') }}" wire:navigate
                    class="flex items-center gap-3 px-3 py-2.5 text-sm rounded-lg hover:bg-gray-50 text-gray-700">
                    My Orders
                </a>

                <form method="POST" action="{{ route('logout') }}" class="mt-2 pt-2 border-t border-gray-100">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 text-sm rounded-lg
                           hover:bg-red-50 hover:text-red-700 text-left transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    @else
        <a href="{{ route('login') }}" wire:navigate class="flex items-center gap-2 p-2 rounded-md hover:bg-gray-100 transition-colors">
            <span class="text-sm font-medium text-gray-700">Login</span>
        </a>
    @endauth
</div>
