<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UiStringResource\Pages;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Models\UiString;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Interface strings for the website. Editors see EN + AR side by side
 * (no locale switcher). Only a super admin can create/delete strings or change keys.
 */
class UiStringResource extends Resource
{
    protected static ?string $model = UiString::class;

    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::SETTINGS);
    }

    public static function getModelLabel(): string
    {
        return __('Interface text');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Interface texts');
    }

    private static function isSuperAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        $locked = fn (string $operation): bool => $operation !== 'create' && ! self::isSuperAdmin();

        return $form->columns(2)->schema([
            TextInput::make('key')
                ->label('Key')
                ->required()
                ->maxLength(100)
                ->regex('/^[a-z0-9_]+(\.[a-z0-9_]+)+$/')
                ->unique(ignoreRecord: true)
                ->helperText(__('group.name, e.g. nav.services. Used by the website code — do not change.'))
                ->disabled($locked),
            TextInput::make('group')
                ->label('Group')
                ->required()
                ->maxLength(50)
                ->regex('/^[a-z0-9_]+$/')
                ->disabled($locked),
            Fields::bilingual('value', 'Text', multiline: true)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup(Group::make('group')->label(__('Group'))->collapsible())
            ->defaultSort('key')
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->fontFamily('mono')
                    ->size('sm')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('value_en')
                    ->label('English')
                    ->state(fn (UiString $record): string => (string) $record->getTranslation('value', 'en', false))
                    ->extraAttributes(['dir' => 'ltr'])
                    ->wrap()
                    // Search both languages in the JSON column.
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('value', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('value_ar')
                    ->label('العربية')
                    ->state(fn (UiString $record): string => (string) $record->getTranslation('value', 'ar', false))
                    ->extraAttributes(['dir' => 'rtl'])
                    ->wrap(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('3xl')
                    ->mutateRecordDataUsing(fn (array $data, UiString $record): array => [
                        ...$data,
                        'value' => $record->getTranslations('value'),
                    ]),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUiStrings::route('/'),
        ];
    }
}
