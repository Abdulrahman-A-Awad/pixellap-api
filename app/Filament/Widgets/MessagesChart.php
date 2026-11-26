<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;

/** Messages per day, last 30 days (bar chart). super_admin + admin only. */
class MessagesChart extends ChartWidget
{
    public const DAYS = 30;

    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected static ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Dashboard::canRefresh();
    }

    public function getHeading(): string
    {
        return __('Messages — last 30 days');
    }

    public function getDescription(): ?string
    {
        return __(':count messages in the last 30 days', ['count' => array_sum(StatsOverview::dailyCounts(self::DAYS))]);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $from = now()->subDays(self::DAYS - 1)->startOfDay()->locale(app()->getLocale());

        return [
            'datasets' => [[
                'label' => __('Messages'),
                'data' => StatsOverview::dailyCounts(self::DAYS),
                'backgroundColor' => 'rgba('.Color::hex('#612FE2')[500].', 0.85)',
                'hoverBackgroundColor' => 'rgba('.Color::hex('#612FE2')[600].', 1)',
                'borderRadius' => 6,
                'maxBarThickness' => 22,
            ]],
            'labels' => collect(range(0, self::DAYS - 1))
                ->map(fn (int $i): string => $from->copy()->addDays($i)->translatedFormat('j M'))
                ->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => 'rgba(127,127,127,0.12)']],
                'x' => ['grid' => ['display' => false], 'ticks' => ['maxRotation' => 0, 'autoSkip' => true, 'maxTicksLimit' => 7, 'autoSkipPadding' => 12]],
            ],
        ];
    }
}
