<?php

declare(strict_types=1);

namespace App\Filament\Support;

/**
 * Admin navigation groups, in display order.
 * Values are English translation keys (see lang/ar.json).
 */
final class NavGroup
{
    public const HOME = 'Home Page';

    public const SERVICES = 'Services';

    public const PAGES = 'Pages';

    public const INBOX = 'Inbox';

    public const SETTINGS = 'Settings';

    public const ACCESS = 'Access';

    /** @return list<string> */
    public static function ordered(): array
    {
        return [self::HOME, self::SERVICES, self::PAGES, self::INBOX, self::SETTINGS, self::ACCESS];
    }
}
