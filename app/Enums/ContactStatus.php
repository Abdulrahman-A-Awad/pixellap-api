<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ContactStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Replied = 'replied';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::Replied => 'Replied',
            self::Closed => 'Closed',
        };
    }

    /** Filament color name. */
    public function color(): string
    {
        return match ($this) {
            self::New => 'info',
            self::InProgress => 'warning',
            self::Replied => 'success',
            self::Closed => 'gray',
        };
    }

    public function getLabel(): string
    {
        return __($this->label());
    }

    public function getColor(): string
    {
        return $this->color();
    }
}
