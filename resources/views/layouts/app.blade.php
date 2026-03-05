<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>


</head>

<body>
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/60 border-b border-gray-200">
        <livewire:navbar />
    </header>

    <main>
        {{ $slot }}
    </main>

    <div>
        @livewire('notifications')
    </div>

    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 sm:bottom-6 sm:right-6">
        @unless (request()->routeIs('products.coach'))
            <a href="{{ route('products.coach') }}" wire:navigate
                class="group relative flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 transition hover:scale-105 hover:bg-indigo-700"
                aria-label="Open product coach">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 10h8m-8 4h5m-7 7l-4 1 1-4a9 9 0 1116.32-3.39A9 9 0 014 21z" />
                </svg>
                <span class="sr-only">Open product coach</span>
            </a>
        @endunless

        @unless (request()->routeIs('cart.index'))
            <livewire:cart-icon :floating="true" />
        @endunless
    </div>

    <footer>
        <livewire:footer />
    </footer>
    @livewireScripts
</body>

</html>
