<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="bg-gray-800 text-white mt-16 pb-8 pt-16 border-t border-gray-700">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12 lg:gap-8 mb-16">

            <div>
                <div class="mb-6 inline-block px-3 py-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Phanna Computer Shop"
                        class="h-12 w-auto object-contain">
                </div>

                <p class="text-gray-400 text-sm mb-6">Let's build your dream PC!</p>

                <div class="flex gap-2">
                    <a href="#"
                        class="w-10 h-10 border border-gray-600 rounded flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 hover:border-gray-500 transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </a>
                    <a href="#"
                        class="w-10 h-10 border border-gray-600 rounded flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 hover:border-gray-500 transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                        </svg>
                    </a>
                    <a href="#"
                        class="w-10 h-10 border border-gray-600 rounded flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 hover:border-gray-500 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </a>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider mb-6">Info & Support</h3>
                <ul class="space-y-4 text-sm">
                    <li><a wire:navigate href="{{ route('products.index') }}" class="text-gray-400 hover:text-white transition uppercase">Shop Now</a></li>
                    <li><a wire:navigate href="{{ route('customer.dashboard') }}" class="text-gray-400 hover:text-white transition uppercase">My Account</a></li>
                    <li><a wire:navigate href="{{ route('customer.orders') }}" class="text-gray-400 hover:text-white transition uppercase">Order Tracking</a></li>
                    <li><a wire:navigate href="{{ route('customer.profile') }}" class="text-gray-400 hover:text-white transition uppercase">Profile Settings</a></li>
                    <li><a wire:navigate href="{{ route('about') }}" class="text-gray-400 hover:text-white transition uppercase">About Us</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition uppercase">Contact Us</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider mb-6">Talk to Us</h3>

                <p class="text-sm text-gray-400 mb-1">Need support?</p>
                <a href="tel:012689168" class="block text-2xl font-bold text-white hover:text-blue-500 transition mb-6">
                    012 689 168
                </a>

                <div class="space-y-4 text-sm text-gray-400">
                    <a href="mailto:phanna968@gmail.com" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <svg class="w-5 h-5 mt-0.5 text-gray-500 group-hover:text-blue-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>phanna968@gmail.com</span>
                    </a>

                    <div>
                        <a href="https://maps.app.goo.gl/gL24Z4UZCvQ986ABA" target="_blank"
                            class="flex items-start gap-3 text-gray-400 hover:text-blue-500 transition group">
                            <svg class="w-5 h-5 mt-1 text-gray-500 group-hover:text-blue-500 transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="leading-relaxed">
                                <strong class="font-semibold text-white group-hover:text-blue-500 transition">Phanna Computer</strong><br>
                                No 114, Road 138, Teuk Laak 2,<br>
                                Near Santhormuk School,<br>
                                Phnom Penh (Visit & Store Pickup)
                            </span>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <div class="border-t border-gray-700 pt-8 flex items-center justify-center">
            <div class="text-sm text-gray-500 text-center">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </div>
    </div>
</div>
