<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="bg-gray-800 text-white mt-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4">{{ config('app.name') }}</h3>
                <p class="text-gray-400">Your one-stop shop for quality products.</p>
            </div>
            <div>
                <h4 class="font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('products.index') }}" class="text-gray-400 hover:text-white">Shop</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">About Us</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-4">Customer Service</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white">Shipping Info</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Returns</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">FAQ</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-4">My Account</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('customer.dashboard') }}" class="text-gray-400 hover:text-white">Dashboard</a>
                    </li>
                    <li><a href="{{ route('customer.orders') }}" class="text-gray-400 hover:text-white">Orders</a></li>
                    <li><a href="{{ route('customer.profile') }}" class="text-gray-400 hover:text-white">Profile</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</div>
</div>
