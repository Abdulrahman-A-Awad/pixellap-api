<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\RevalidationService;
use App\Support\CacheTags;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /** Roles allowed to force a full website refresh. */
    public const REFRESH_ROLES = [AppServiceProvider::SUPER_ADMIN_ROLE, 'admin'];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshWebsiteCache')
                ->label(__('Refresh website cache'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('Clears the API cache and asks the website to reload every page. Use it if a change does not appear on the website.'))
                ->visible(fn (): bool => self::canRefresh())
                ->action(function (RevalidationService $revalidation): void {
                    $revalidation->touch(CacheTags::all());

                    Notification::make()
                        ->success()
                        ->title(__('Website refresh requested'))
                        ->body(__('Pages update within a few seconds once the queue worker runs.'))
                        ->send();
                }),
            Action::make('viewWebsite')
                ->label(__('View website'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): string => (string) config('pixellap.frontend_url'), shouldOpenInNewTab: true),
        ];
    }

    public static function canRefresh(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAnyRole(self::REFRESH_ROLES);
    }
}
