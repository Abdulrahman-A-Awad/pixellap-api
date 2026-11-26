@php
    $user = filament()->auth()->user();
    $roles = $user?->roles?->pluck('name')->map(fn ($r) => \App\Filament\Support\PermissionMatrix::roleLabel($r))->implode(' · ');
@endphp

@if ($user)
    <div class="px-usermenu" data-user-header>
        <x-filament-panels::avatar.user :user="$user" size="lg" />
        <div class="px-usermenu__text">
            <p class="px-usermenu__name">{{ filament()->getUserName($user) }}</p>
            @if ($roles)
                <p class="px-usermenu__role">{{ $roles }}</p>
            @endif
        </div>
    </div>
@endif
