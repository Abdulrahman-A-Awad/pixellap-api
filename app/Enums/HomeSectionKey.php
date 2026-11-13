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

    public function getLabel(): string
    {
        return $this->label();
    }
}
