@php($arrow = app()->getLocale() === 'ar' ? 'heroicon-m-arrow-small-left' : 'heroicon-m-arrow-small-right')

<x-filament-widgets::widget class="px-ready">
    @if ($allSet)
        <div class="px-ready__done" data-ready="all-set">
            <x-filament::icon icon="heroicon-s-check-badge" class="px-ready__done-icon" />
            <span class="px-ready__done-title">{{ __('Website readiness') }}</span>
            <x-filament::badge color="success" size="sm">{{ __('All set ✓') }}</x-filament::badge>
        </div>
    @else
        <x-filament::section icon="heroicon-o-rocket-launch" icon-color="primary" collapsible persist-collapsed id="website-readiness">
            <x-slot name="heading">{{ __('Website readiness') }}</x-slot>
            <x-slot name="description">{{ __(':done of :total done', ['done' => $done, 'total' => $total]) }}</x-slot>

            <div class="px-ready__bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $done }}" data-ready="{{ $done }}/{{ $total }}">
                <span class="px-ready__fill" style="inline-size: {{ $percent }}%"></span>
            </div>

            <ul class="px-ready__list">
                @foreach ($remaining as $check)
                    <li class="px-ready__item" data-check="{{ $check['key'] }}" data-ok="0">
                        <x-filament::icon icon="heroicon-o-exclamation-circle" class="px-ready__warn" />
                        <div class="px-ready__text">
                            <p class="px-ready__label">{{ __($check['label']) }}</p>
                            @if ($check['detail'])
                                <p class="px-ready__detail">
                                    {{ collect($check['detail'])->map(fn ($d) => __($d))->implode(app()->getLocale() === 'ar' ? '، ' : ', ') }}
                                </p>
                            @endif
                        </div>
                        @if ($check['url'])
                            <x-filament::link :href="$check['url']" size="sm" :icon="$arrow" icon-position="after">
                                {{ __('Fix') }}
                            </x-filament::link>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
