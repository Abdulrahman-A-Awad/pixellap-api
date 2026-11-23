<?php

declare(strict_types=1);

use App\Models\UiString;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds the interface strings of the redesigned contact page (quick contact cards, service chips,
 * character counter, error summary, "What happens next", short FAQ) on existing installations.
 * Only keys that do not exist yet are created: strings editors changed in the admin are never
 * touched (UiStringSeeder would overwrite them). Fresh installs get them from the seeder.
 */
return new class extends Migration
{
    private const KEYS = [
        'form.other',
        'form.char_count',
        'form.error_summary',
        'contact.quick_title',
        'contact.chat_title',
        'contact.email_title',
        'contact.call_title',
        'contact.next_title',
        'contact.next_1_title',
        'contact.next_1_text',
        'contact.next_2_title',
        'contact.next_2_text',
        'contact.next_3_title',
        'contact.next_3_text',
        'contact.faq_title',
        'contact.faq_more',
    ];

    public function up(): void
    {
        /** @var array<string, array<string, array{0: string, 1: string}>> $groups */
        $groups = require database_path('seeders/data/ui_strings.php');

        foreach (self::KEYS as $fullKey) {
            [$group, $key] = explode('.', $fullKey, 2);
            if (! isset($groups[$group][$key])) {
                continue;
            }
            [$en, $ar] = $groups[$group][$key];
            UiString::firstOrCreate(
                ['key' => $fullKey],
                ['group' => $group, 'value' => ['en' => $en, 'ar' => $ar]],
            );
        }
    }

    public function down(): void
    {
        // Content rows: left in place (they may have been edited since).
    }
};
