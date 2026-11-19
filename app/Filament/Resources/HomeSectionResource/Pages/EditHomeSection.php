<?php

declare(strict_types=1);

namespace App\Filament\Resources\HomeSectionResource\Pages;

use App\Enums\HomeSectionKey;
use App\Filament\Resources\HomeSectionResource;
use App\Filament\Widgets\HomeItems;
use App\Models\Client;
use App\Models\Faq;
use App\Models\HomeSection;
use App\Models\ProcessStep;
use App\Models\Stat;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomeSection extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = HomeSectionResource::class;

    /** Section key → [items table, model whose viewAny permission it needs]. */
    public const ITEMS = [
        'stats' => [HomeItems\StatsTable::class, Stat::class],
        'process' => [HomeItems\ProcessStepsTable::class, ProcessStep::class],
        'clients' => [HomeItems\ClientsTable::class, Client::class],
        'faq' => [HomeItems\HomeFaqsTable::class, Faq::class],
    ];

    public function getSubheading(): ?string
    {
        $key = $this->getRecord() instanceof HomeSection ? $this->getRecord()->key : null;

        return $key instanceof HomeSectionKey ? __($key->description()) : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }

    /** The section's own items (numbers, steps, logos, questions) under the form. */
    protected function getFooterWidgets(): array
    {
        $record = $this->getRecord();
        $items = $record instanceof HomeSection ? (self::ITEMS[$record->key->value] ?? null) : null;

        if ($items === null || ! (auth()->user()?->can('viewAny', $items[1]) ?? false)) {
            return [];
        }

        return [$items[0]];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }
}
