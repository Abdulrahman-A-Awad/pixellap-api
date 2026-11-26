@php
    use App\Filament\Support\PermissionMatrix;

    $areas = PermissionMatrix::areas();
    $actions = PermissionMatrix::ACTIONS;
    $path = $getStatePath();
    $state = (array) ($getState() ?? []);
    $isOn = fn (string $area, string $action): bool => (bool) ($state[$area][$action] ?? false);
    $rowAll = fn (string $area): bool => collect($areas[$area]['actions'])->every(fn ($a) => $isOn($area, $a));
    $allOn = collect($areas)->keys()->every(fn ($area) => $rowAll($area));
    $disabled = $isDisabled();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="px-matrix" data-matrix>
        <div class="px-matrix__toolbar">
            <label class="px-matrix__all">
                <x-filament::input.checkbox
                    :checked="$allOn"
                    :disabled="$disabled"
                    wire:click="toggleMatrixAll"
                    data-matrix-all
                />
                <span>{{ __('Select all') }}</span>
            </label>
            <span class="px-matrix__hint">{{ __('— means the action does not exist for that area.') }}</span>
        </div>

        <div class="px-matrix__table" role="table" aria-label="{{ __('Permissions') }}">
            <div class="px-matrix__row px-matrix__row--head" role="row">
                <div class="px-matrix__area" role="columnheader">{{ __('Area') }}</div>
                @foreach ($actions as $action)
                    <div class="px-matrix__cell" role="columnheader">{{ __(PermissionMatrix::ACTION_LABELS[$action]) }}</div>
                @endforeach
                <div class="px-matrix__cell px-matrix__cell--all" role="columnheader">{{ __('All') }}</div>
            </div>

            @foreach ($areas as $key => $area)
                <div class="px-matrix__row" role="row" data-area="{{ $key }}">
                    <div class="px-matrix__area" role="rowheader">
                        <x-filament::icon :icon="$area['icon']" class="px-matrix__icon" />
                        <div>
                            <div class="px-matrix__label">{{ __($area['label']) }}</div>
                            <div class="px-matrix__help">{{ __($area['help']) }}</div>
                        </div>
                    </div>

                    @foreach ($actions as $action)
                        <div class="px-matrix__cell" role="cell" data-label="{{ __(PermissionMatrix::ACTION_LABELS[$action]) }}">
                            @if (PermissionMatrix::applies($key, $action))
                                <label class="px-matrix__check">
                                    <x-filament::input.checkbox
                                        wire:model.live="{{ $path }}.{{ $key }}.{{ $action }}"
                                        :disabled="$disabled"
                                        data-cell="{{ $key }}.{{ $action }}"
                                    />
                                    <span class="px-matrix__sr">{{ __($area['label']) }}: {{ __(PermissionMatrix::ACTION_LABELS[$action]) }}</span>
                                </label>
                            @else
                                <span class="px-matrix__none" aria-label="{{ __('Not applicable') }}">—</span>
                            @endif
                        </div>
                    @endforeach

                    <div class="px-matrix__cell px-matrix__cell--all" role="cell" data-label="{{ __('All') }}">
                        <label class="px-matrix__check">
                            <x-filament::input.checkbox
                                :checked="$rowAll($key)"
                                :disabled="$disabled"
                                wire:click="toggleMatrixRow('{{ $key }}')"
                                data-row-all="{{ $key }}"
                            />
                            <span class="px-matrix__sr">{{ __('Select all') }}: {{ __($area['label']) }}</span>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
