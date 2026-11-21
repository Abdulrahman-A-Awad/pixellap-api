<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum HomeSectionKey: string implements HasLabel
{
    case Hero = 'hero';
    case Stats = 'stats';
    case Services = 'services';
    case Process = 'process';
    case Clients = 'clients';
    case About = 'about';
    case Faq = 'faq';
    case ContactCta = 'contact_cta';

    /** Friendly name shown in the admin (English key, translated via lang/ar.json). */
    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::Stats => 'Numbers',
            self::Services => 'Our services',
            self::Process => 'How we work',
            self::Clients => 'Clients',
            self::About => 'About teaser',
            self::Faq => 'FAQ',
            self::ContactCta => 'Contact banner',
        };
    }

    /** One line: what this section controls on the home page. */
    public function description(): string
    {
        return match ($this) {
            self::Hero => 'The first screen: headline, buttons and the loop videos.',
            self::Stats => 'Key numbers with a count-up animation.',
            self::Services => 'Cards for each service category (edited under Services).',
            self::Process => 'The steps of how a project runs.',
            self::Clients => 'A row of client logos (hidden while there are none).',
            self::About => 'Short introduction with a photo and a link to the About page.',
            self::Faq => 'Questions and answers about working with us.',
            self::ContactCta => 'The closing banner inviting visitors to get in touch.',
        };
    }

    /**
     * Form fields each section renders on the website (others are hidden in the admin and
     * always null in the API).
     *
     * @return list<string>
     */
    public function fields(): array
    {
        $base = ['eyebrow', 'title', 'subtitle'];

        return match ($this) {
            // Hero media = the loop video variants (HomeSection::HERO_MEDIA), not the plain image.
            // No eyebrow: the hero stays calm (the column is kept, the field is hidden).
            self::Hero => ['title', 'subtitle', 'cta', 'cta2', 'media'],
            self::Services => [...$base, 'cta'],
            self::About => [...$base, 'cta', 'image'],
            // The banner shows a title, a short text and one button (no eyebrow, no image).
            self::ContactCta => ['title', 'subtitle', 'cta'],
            default => $base,
        };
    }

    public function supports(string $field): bool
    {
        return in_array($field, $this->fields(), true);
    }

    public function getLabel(): string
    {
        return __($this->label());
    }
}
