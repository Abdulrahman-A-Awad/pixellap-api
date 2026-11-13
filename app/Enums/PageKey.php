<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PageKey: string implements HasLabel
{
    case About = 'about';
    case Services = 'services';
    case Contact = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::About => 'About',
            self::Services => 'Services',
            self::Contact => 'Contact',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
