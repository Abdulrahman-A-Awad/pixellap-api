<x-filament-panels::page>
    {{ $this->infolist }}

    <x-filament-panels::form wire:submit="saveTriage">
        {{ $this->triageForm }}

        @if ($this->canTriage())
            <div>
                <x-filament::button type="submit">
                    {{ __('Save') }}
                </x-filament::button>
            </div>
        @endif
    </x-filament-panels::form>
</x-filament-panels::page>
