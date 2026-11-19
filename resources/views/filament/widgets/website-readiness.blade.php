<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-rocket-launch" :icon-color="$allSet ? 'success' : 'primary'">
        <x-slot name="heading">{{ __('Website readiness') }}</x-slot>
        <x-slot name="description">
            @if ($allSet)
                {{ __('All set ✓') }}
            @else
                {{ __(':done of :total done', ['done' => $done, 'total' => count($checks)]) }}
            @endif
        </x-slot>

        @if ($allSet)
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Everything the website needs is in place.') }}</p>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($checks as $check)
                    <li class="flex items-start gap-3 py-2.5" data-check="{{ $check['key'] }}" data-ok="{{ $check['ok'] ? '1' : '0' }}">
                        @if ($check['ok'])
                            <x-filament::icon icon="heroicon-s-check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-success-500" />
                        @else
                            <x-filament::icon icon="heroicon-o-exclamation-circle" class="mt-0.5 h-5 w-5 shrink-0 text-warning-500" />
                        @endif

                        <div class="min-w-0 flex-1">
                            <p @class(['text-sm', 'text-gray-500 dark:text-gray-400' => $check['ok'], 'font-medium text-gray-950 dark:text-white' => ! $check['ok']])>
                                {{ __($check['label']) }}
                            </p>
                            @if (! $check['ok'] && $check['detail'])
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ collect($check['detail'])->map(fn ($d) => __($d))->implode(app()->getLocale() === 'ar' ? '، ' : ', ') }}
                                </p>
                            @endif
                        </div>

                        @if (! $check['ok'] && $check['url'])
                            <x-filament::link :href="$check['url']" size="sm" :icon="app()->getLocale() === 'ar' ? 'heroicon-m-arrow-small-left' : 'heroicon-m-arrow-small-right'" icon-position="after">
                                {{ __('Fix') }}
                            </x-filament::link>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
