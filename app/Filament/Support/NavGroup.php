<?php

declare(strict_types=1);

namespace App\Filament\Support;

/**
 * Admin navigation groups, in display order. Dashboard and Messages sit above them, ungrouped.
 * Values are English translation keys (see lang/ar.json).
 */
final class NavGroup
{
    public const CONTENT = 'Content';

    public const SETTINGS = 'Settings';

    /** Users, roles and the activity log — shown to super admins only. */
    public const ACCESS = 'Access';

    /** @return list<string> */
    public static function ordered(): array
    {
        return [self::CONTENT, self::SETTINGS, self::ACCESS];
    }
}
