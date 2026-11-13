<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactMessageResource;
use App\Models\Client;
use App\Models\ContactMessage;
use App\Models\Service;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $new = ContactMessage::query()->where('status', ContactStatus::New)->count();

        return [
            Stat::make(__('New messages'), $new)
                ->icon('heroicon-o-inbox')
                ->color($new > 0 ? 'warning' : 'gray')
                ->url(ContactMessageResource::getUrl('index', ['tableFilters' => ['status' => ['value' => ContactStatus::New->value]]])),
            Stat::make(__('Messages this month'), ContactMessage::query()->where('created_at', '>=', now()->startOfMonth())->count())
                ->icon('heroicon-o-envelope'),
            Stat::make(__('Visible services'), Service::query()->visible()->whereHas('serviceCategory', fn ($q) => $q->where('is_visible', true))->count())
                ->icon('heroicon-o-squares-2x2'),
            Stat::make(__('Clients'), Client::query()->visible()->count())
                ->icon('heroicon-o-building-office-2'),
        ];
    }
}
