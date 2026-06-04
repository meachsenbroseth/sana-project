@php
    $currentLocale = app()->getLocale();
    $redirect = url()->full();
@endphp

<div class="flex items-center gap-2">
    <x-filament::button
        tag="a"
        :href="route('locale.switch', ['locale' => 'en', 'redirect' => $redirect])"
        size="sm"
        :color="$currentLocale === 'en' ? 'primary' : 'gray'"
        :outlined="$currentLocale !== 'en'"
    >
        EN
    </x-filament::button>

    <x-filament::button
        tag="a"
        :href="route('locale.switch', ['locale' => 'km', 'redirect' => $redirect])"
        size="sm"
        :color="$currentLocale === 'km' ? 'primary' : 'gray'"
        :outlined="$currentLocale !== 'km'"
    >
        KM
    </x-filament::button>
</div>
