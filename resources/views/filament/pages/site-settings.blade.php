<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit">
                Save Settings
            </x-filament::button>

            <x-filament::link :href="$this->getShippingMethodsUrl()">
                Manage Shipping Methods
            </x-filament::link>
        </div>
    </form>
</x-filament-panels::page>
