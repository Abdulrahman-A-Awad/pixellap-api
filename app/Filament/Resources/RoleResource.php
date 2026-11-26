<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Support\NavGroup;
use App\Filament\Support\PermissionMatrix;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Spatie\Permission\Models\Role;

/**
 * Simple roles editor (replaces Filament Shield's resource: Shield registers its own only
 * when no "\RoleResource" exists in the panel).
 *
 * One matrix of friendly areas × View / Add / Edit / Delete / Reorder (App\Filament\Support\PermissionMatrix).
 * The guard is always "web". super_admin is shown as "Full access" and can never be edited or
 * deleted (also enforced in Gate::before); built-in roles keep their name and cannot be deleted.
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $slug = 'roles';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = false;

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::ACCESS);
    }

    public static function getModelLabel(): string
    {
        return __('Role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Roles');
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof Role ? PermissionMatrix::roleLabel($record->name) : '';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('guard_name', 'web')->withCount('users');
    }

    public static function canEdit(Model $record): bool
    {
        return ! PermissionMatrix::isSuperAdminRole($record) && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return ! PermissionMatrix::isBuiltIn($record) && parent::canDelete($record);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Section::make(__('Role'))
                    ->description(__('A name your team understands, e.g. "Designers".'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(60)
                            ->autocomplete('off')
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('guard_name', 'web'))
                            // Built-in roles are referenced by the code (notifications, dashboard): keep their name.
                            ->disabled(fn (?Role $record): bool => PermissionMatrix::isBuiltIn($record))
                            ->dehydrated(fn (?Role $record): bool => ! PermissionMatrix::isBuiltIn($record))
                            ->formatStateUsing(fn (?string $state, ?Role $record): ?string => $record && PermissionMatrix::isBuiltIn($record)
                                ? PermissionMatrix::roleLabel($record->name) : $state)
                            ->helperText(fn (?Role $record): ?string => PermissionMatrix::isBuiltIn($record)
                                ? __('Built-in role: the name cannot be changed.') : null),
                        TextInput::make('description')
                            ->label('Short description')
                            ->maxLength(255)
                            ->autocomplete('off')
                            ->placeholder(fn (?Role $record): ?string => $record ? PermissionMatrix::roleDescription($record) : null)
                            ->helperText(__('One line, shown in the roles list and when choosing a role for a user.')),
                    ]),
                Section::make(__('What can this role do?'))
                    ->description(__('Tick what people with this role may do in each area. Dashboard boxes follow "View".'))
                    ->schema([
                        ViewField::make('matrix')
                            ->hiddenLabel()
                            ->view('filament.roles.permission-matrix')
                            ->default(PermissionMatrix::emptyState())
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->weight('bold')
                    ->formatStateUsing(fn (string $state): string => PermissionMatrix::roleLabel($state))
                    ->description(fn (Role $record): ?string => PermissionMatrix::roleDescription($record))
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('access')
                    ->label('Access')
                    ->badge()
                    ->state(fn (Role $record): string => PermissionMatrix::isSuperAdminRole($record)
                        ? __('Full access')
                        : trans_choice(':count permission|:count permissions', PermissionMatrix::countOn($record->permissions->pluck('name')), ['count' => PermissionMatrix::countOn($record->permissions->pluck('name'))]))
                    ->color(fn (Role $record): string => PermissionMatrix::isSuperAdminRole($record) ? 'primary' : 'gray')
                    ->icon(fn (Role $record): ?string => PermissionMatrix::isSuperAdminRole($record) ? 'heroicon-m-shield-check' : null),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-users')
                    ->alignCenter(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('permissions'))
            ->recordUrl(fn (Role $record): ?string => static::canEdit($record) ? static::getUrl('edit', ['record' => $record]) : null)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription(__('People with this role lose its permissions. Their accounts stay.')),
            ])
            ->emptyStateIcon('heroicon-o-shield-check')
            ->emptyStateHeading(__('No roles yet'))
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
