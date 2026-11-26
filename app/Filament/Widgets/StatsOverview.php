<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactMessageResource;
use App\Filament\Resources\ServiceCategoryResource;
use App\Filament\Support\PermissionMatrix;
use App\Models\ContactMessage;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * KPI row: new messages, this month (vs last month), total messages, visible services.
 * Visible to anyone who can view Messages; the services card needs Services → View.
 */
class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return PermissionMatrix::can('messages');
    }

    protected function getColumns(): int
    {
        return count($this->getCachedStats()) >= 4 ? 4 : 3;
    }

    protected function getStats(): array
    {
        $new = ContactMessage::query()->where('status', ContactStatus::New)->count();
        $thisMonth = ContactMessage::query()->where('created_at', '>=', now()->startOfMonth())->count();
        $lastMonth = ContactMessage::query()
            ->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth()])
            ->count();
        $change = self::percentChange($thisMonth, $lastMonth);

        $stats = [
            Stat::make(__('New messages'), self::number($new))
                ->description($new > 0 ? __('Waiting for a reply') : __('All caught up'))
                ->descriptionIcon($new > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-inbox')
                ->color($new > 0 ? 'warning' : 'success')
                ->url(ContactMessageResource::getUrl('index', ['tableFilters' => ['status' => ['value' => ContactStatus::New->value]]]))
                ->extraAttributes(['data-kpi' => 'new']),
            Stat::make(__('Messages this month'), self::number($thisMonth))
                ->description(match (true) {
                    $change === null => __('No messages last month'),
                    $change === 0 => __('Same as last month'),
                    default => __(':percent vs last month', ['percent' => ($change > 0 ? '+' : '−').abs($change).'%']),
                })
                ->descriptionIcon(match (true) {
                    $change === null || $change === 0 => 'heroicon-m-minus',
                    $change > 0 => 'heroicon-m-arrow-trending-up',
                    default => 'heroicon-m-arrow-trending-down',
                })
                ->icon('heroicon-o-envelope')
                ->color(($change ?? 0) < 0 ? 'danger' : 'primary')
                ->chart(self::dailyCounts(30))
                ->extraAttributes(['data-kpi' => 'month']),
            Stat::make(__('Total messages'), self::number(ContactMessage::query()->count()))
                ->description(__('Since the website opened'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->chart(self::monthlyCounts(6))
                ->url(ContactMessageResource::getUrl('index'))
                ->extraAttributes(['data-kpi' => 'total']),
        ];

        if (PermissionMatrix::can('services')) {
            $stats[] = Stat::make(__('Services visible'), self::number(Service::query()->visible()->count()))
                ->description(__('Shown on the website'))
                ->icon('heroicon-o-squares-2x2')
                ->color('info')
                ->url(ServiceCategoryResource::getUrl('index'))
                ->extraAttributes(['data-kpi' => 'services']);
        }

        return $stats;
    }

    /** Whole-percent change, null when there is nothing to compare with. */
    public static function percentChange(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return $current === 0 ? 0 : null;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }

    private static function number(int $value): string
    {
        return number_format($value);
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

    /** @return list<int> messages per calendar month, oldest first */
    public static function monthlyCounts(int $months): array
    {
        $from = now()->subMonthsNoOverflow($months - 1)->startOfMonth();
        $perMonth = ContactMessage::query()
            ->where('created_at', '>=', $from)
            ->pluck('created_at')
            ->countBy(fn (Carbon $date): string => $date->format('Y-m'));

        return collect(range(0, $months - 1))
            ->map(fn (int $i): int => (int) ($perMonth[$from->copy()->addMonthsNoOverflow($i)->format('Y-m')] ?? 0))
            ->all();
    }
}
