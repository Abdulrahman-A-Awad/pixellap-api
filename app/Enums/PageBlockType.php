<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PageBlockType: string implements HasLabel
{
    case Text = 'text';
    case ImageText = 'image_text';
    case MissionVision = 'mission_vision';
    case Values = 'values';
    case Cta = 'cta';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::ImageText => 'Image + text',
            self::MissionVision => 'Mission & vision',
            self::Values => 'Values',
            self::Cta => 'Call to action',
        };
    }

    public function getLabel(): string
    {
        return __($this->label());
    }
}
