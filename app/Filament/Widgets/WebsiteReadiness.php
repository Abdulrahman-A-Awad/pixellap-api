<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Filament\Support\PermissionMatrix;
use App\Support\WebsiteReadiness as Readiness;
use Filament\Widgets\Widget;

/**
 * Launch checklist as a compact progress bar ("5 of 9") with the remaining items;
 * collapses to a small "All set ✓" badge once everything is in place.
 * For admins and content editors (anyone who edits the home page or the site settings).
 */
class WebsiteReadiness extends Widget
{
    protected static string $view = 'filament.widgets.website-readiness';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Dashboard::canRefresh() // super_admin + admin
            || PermissionMatrix::can('home', 'update')
            || PermissionMatrix::can('settings', 'update');
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $checks = Readiness::checks();
        $done = count(array_filter($checks, fn (array $c): bool => $c['ok']));

        return [
            'checks' => $checks,
            'remaining' => array_values(array_filter($checks, fn (array $c): bool => ! $c['ok'])),
            'done' => $done,
            'total' => count($checks),
            'percent' => $checks === [] ? 100 : (int) round($done / count($checks) * 100),
            'allSet' => Readiness::allSet(),
        ];
    }
}
