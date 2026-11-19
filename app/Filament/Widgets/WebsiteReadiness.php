<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Support\WebsiteReadiness as Readiness;
use Filament\Widgets\Widget;

/** Launch checklist (admins only): what is still missing before the website is complete. */
class WebsiteReadiness extends Widget
{
    protected static string $view = 'filament.widgets.website-readiness';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Dashboard::canRefresh(); // super_admin + admin
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $checks = Readiness::checks();

        return [
            'checks' => $checks,
            'done' => count(array_filter($checks, fn (array $c): bool => $c['ok'])),
            'allSet' => Readiness::allSet(),
        ];
    }
}
