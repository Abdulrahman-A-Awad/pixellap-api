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
            self::Hero => 'The first screen: short text, buttons and the loop videos.',
            self::Stats => 'Shown as a strip under “About”. Turn off to hide the numbers.',
            self::Services => 'Cards for each service category (edited under Services).',
            self::Process => 'The steps of how a project runs.',
            self::Clients => 'A row of client logos (hidden while there are none).',
            self::About => 'Short introduction with a photo and a link to the About page.',
            self::Faq => 'Questions and answers about working with us.',
            self::ContactCta => 'The closing banner inviting visitors to get in touch.',
        };
    }

    /** "Where this appears": shown at the top of the section's edit form. */
    public function where(): string
    {
        return match ($this) {
            self::Hero => 'Top of the home page. The big headline is the site tagline (Site settings → General); here you edit the short text under it and the two buttons. A “Watch the reel” button is added automatically when a showreel link is set in Site settings.',
            self::Stats => 'A row of number tiles under the “About” section on the home page (and on the About page). The small label and title appear as a heading above the tiles.',
            self::Services => 'The stacked service cards on the home page. The cards come from Services; here you edit the heading and the link on the right of it (“Explore our services”).',
            self::Process => 'The “How we work” steps on the home page, connected by a line.',
            self::Clients => 'A scrolling row of client logos on the home page.',
            self::About => 'The photo + short introduction on the home page, with the numbers strip below it.',
            self::Faq => 'The questions on the home page (service pages have their own questions under Services).',
            self::ContactCta => 'The coloured banner at the end of the home page, above the footer.',
        };
    }

    /**
     * Sections whose website block needs items: they stay hidden until at least one VISIBLE item
     * exists (the editor sees a notice in the form and a "Hidden: no items" badge in the list).
     * Value = the plain-language notice.
     */
    public function emptyNotice(): ?string
    {
        return match ($this) {
            self::Stats => 'The numbers strip stays hidden on the website until you add at least one number.',
            self::Process => 'This section stays hidden on the website until you add at least one step.',
            self::Clients => 'This section stays hidden on the website until you add at least one client logo.',
            self::Faq => 'This section stays hidden on the website until you add at least one question.',
            self::Services => 'This section stays hidden on the website until at least one service category is visible (Services).',
            default => null,
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
            // No eyebrow and no title: the H1 is always the tagline setting (one place to edit).
            // The columns are kept; the API returns them as null.
            self::Hero => ['subtitle', 'cta', 'cta2', 'media'],
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
