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

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::Stats => 'Stats',
            self::Services => 'Services',
            self::Process => 'Process',
            self::Clients => 'Clients',
            self::About => 'About',
            self::Faq => 'FAQ',
            self::ContactCta => 'Contact call-to-action',
        };
    }

    /**
     * Form fields each section uses (others are hidden in the admin).
     *
     * @return list<string>
     */
    public function fields(): array
    {
        $base = ['eyebrow', 'title', 'subtitle'];

        return match ($this) {
            self::Hero => [...$base, 'cta', 'cta2', 'image', 'video'],
            self::Services => [...$base, 'cta'],
            self::About, self::ContactCta => [...$base, 'cta', 'image'],
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
