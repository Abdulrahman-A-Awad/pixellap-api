<x-filament-widgets::widget class="px-quick">
    <x-filament::section>
        <x-slot name="heading">{{ __('Quick actions') }}</x-slot>

        <div class="px-quick__grid">
            @foreach ($tiles as $tile)
                <a href="{{ $tile['url'] }}" class="px-quick__tile" data-tile="{{ $tile['key'] }}">
                    <span class="px-quick__icon">
                        <x-filament::icon :icon="$tile['icon']" />
                    </span>
                    <span class="px-quick__text">
                        <span class="px-quick__label">{{ $tile['label'] }}</span>
                        <span class="px-quick__help">{{ $tile['help'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
