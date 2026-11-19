<?php

declare(strict_types=1);

namespace App\Filament\Widgets\HomeItems;

use App\Filament\Concerns\RevalidatesOnReorder;
use App\Filament\Support\TableKit;
use App\Models\HomeSection;
use Filament\Forms\Components\Component;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The items of one home section (stats, process steps, clients, home FAQs), shown under the
 * section's edit form (EditHomeSection footer). Create / edit / delete in modals, drag to reorder.
 *
 * Permissions come from each model's policy (content editors cannot delete). Reordering
 * revalidates the website and is logged on the home section (RevalidatesOnReorder).
 * Translatable subclasses add `Concerns\Translatable` + a LocaleSwitcher, like relation managers.
 */
abstract class HomeItemsTable extends TableWidget
{
    use RevalidatesOnReorder;

    /** The home section being edited (passed in by EditHomeSection). */
    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    /** Part of the edit page: render with it (no lazy placeholder). */
    protected static bool $isLazy = false;

    /** Never in the dashboard: only rendered by EditHomeSection. */
    protected static bool $isDiscovered = false;

    /** @return class-string<Model> */
    abstract protected function model(): string;

    abstract protected function heading(): string;

    abstract protected function itemLabel(): string;

    abstract protected function itemPluralLabel(): string;

    /** @return list<Tables\Columns\Column> */
    abstract protected function columns(): array;

    /** @return array<Component> */
    abstract protected function formSchema(): array;

    protected function query(): Builder
    {
        return $this->model()::query();
    }

    /** Header actions before "Add" (e.g. the language switcher). */
    protected function extraHeaderActions(): array
    {
        return [];
    }

    /** Used by RevalidatesOnReorder: log reorders on the section being edited. */
    public function getOwnerRecord(): ?HomeSection
    {
        return $this->record instanceof HomeSection ? $this->record : null;
    }

    public function table(Table $table): Table
    {
        $model = $this->model();
        $user = auth()->user();

        return $table
            ->query($this->query())
            ->heading(__($this->heading()))
            ->modelLabel(__($this->itemLabel()))
            ->pluralModelLabel(__($this->itemPluralLabel()))
            ->reorderable('sort', fn (): bool => (bool) $user?->can('reorder', $model))
            ->defaultSort('sort')
            ->paginated(false)
            ->columns([...$this->columns(), TableKit::visibleToggle()])
            ->emptyStateHeading(__('Nothing here yet'))
            ->emptyStateDescription(__('Use “Add” to create the first one.'))
            ->headerActions([
                ...$this->extraHeaderActions(),
                Tables\Actions\CreateAction::make()
                    ->label(__('Add'))
                    ->form($this->formSchema())
                    ->visible(fn (): bool => (bool) $user?->can('create', $model)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form($this->formSchema())
                    ->visible(fn (Model $record): bool => (bool) $user?->can('update', $record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Model $record): bool => (bool) $user?->can('delete', $record)),
            ]);
    }

    public static function canView(): bool
    {
        return true; // shown only by EditHomeSection, which checks viewAny per model
    }
}
