<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $new = ContactMessage::query()->where('status', ContactStatus::New)->count();

        return [
            Stat::make(__('New messages'), $new)
                ->description($new > 0 ? __('Waiting for a reply') : __('All caught up'))
                ->icon('heroicon-o-inbox')
                ->color($new > 0 ? 'warning' : 'gray')
                ->url(ContactMessageResource::getUrl('index', ['tableFilters' => ['status' => ['value' => ContactStatus::New->value]]])),
            Stat::make(__('Messages this month'), ContactMessage::query()->where('created_at', '>=', now()->startOfMonth())->count())
                ->description(__('Last 30 days'))
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->chart(self::dailyCounts(30)),
        ];
    }

    /**
     * Messages per day for the last `$days` days (oldest first), for the sparkline.
     *
     * @return list<int>
     */
    public static function dailyCounts(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $perDay = ContactMessage::query()
            ->where('created_at', '>=', $from)
            ->pluck('created_at')
            ->countBy(fn (Carbon $date): string => $date->toDateString());

        return collect(range(0, $days - 1))
            ->map(fn (int $i): int => (int) ($perDay[$from->copy()->addDays($i)->toDateString()] ?? 0))
            ->all();
    }
}
