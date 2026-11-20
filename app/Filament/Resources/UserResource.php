<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Closure;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

/**
 * Staff accounts.
 *
 * Rules (enforced here AND in UserPolicy):
 * - Only a super admin can see, edit or delete super admins, or grant the super_admin role.
 * - Nobody can delete or deactivate themselves.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::ACCESS);
    }

    public static function getModelLabel(): string
    {
        return __('User');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Users');
    }

    private static function actor(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private static function superAdminRoleId(): ?int
    {
        return Role::query()->where('name', AppServiceProvider::SUPER_ADMIN_ROLE)->value('id');
    }

    private static function isSelf(?User $record): bool
    {
        return $record !== null && self::actor()?->is($record);
    }

    /** Non-super admins never see super admin accounts. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('roles');

        if (! self::actor()?->isSuperAdmin()) {
            $query->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', AppServiceProvider::SUPER_ADMIN_ROLE));
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Group::make()->columnSpan(2)->schema([
                    Section::make(__('Account'))->columns(2)->schema([
                        TextInput::make('name')->label('Name')->required()->maxLength(255)
                            ->helperText(__('Shown in the admin only.')),
                        TextInput::make('email')->label('Email')->email()->required()->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state)) // hashed by the model cast
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? __('Leave empty to keep the current password.') : null),
                        Select::make('admin_locale')
                            ->label('Admin language')
                            ->helperText(__('Language of this admin panel for this person.'))
                            ->options(['en' => 'English', 'ar' => 'العربية'])
                            ->default('en')
                            ->required()
                            ->native(false),
                    ]),
                    Section::make(__('Access'))->columns(2)->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->helperText(__('Admin: everything. Content editor: edits content, cannot delete. Sales: messages only.'))
                            ->relationship(
                                'roles',
                                'name',
                                modifyQueryUsing: fn (Builder $query) => self::actor()?->isSuperAdmin()
                                    ? $query
                                    : $query->where('name', '!=', AppServiceProvider::SUPER_ADMIN_ROLE),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record): string => __(str($record->name)->headline()->toString()))
                            // Hiding the option is not enough: Filament syncs whatever IDs are submitted.
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! self::actor()?->isSuperAdmin() && in_array(self::superAdminRoleId(), array_map('intval', (array) $value), true)) {
                                    $fail(__('Only a super admin can grant the super admin role.'));
                                }
                            })
                            ->saveRelationshipsUsing(function (Select $component, User $record, ?array $state): void {
                                $ids = array_map('intval', $state ?? []);
                                if (! self::actor()?->isSuperAdmin()) {
                                    $ids = array_values(array_diff($ids, [self::superAdminRoleId()]));
                                }
                                $component->getRelationship()->sync($ids);
                            })
                            ->multiple()
                            ->preload()
                            ->native(false),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false)
                            ->disabled(fn (?User $record): bool => self::isSelf($record))
                            ->helperText(fn (?User $record): ?string => self::isSelf($record)
                                ? __('You cannot deactivate your own account.') : __('Inactive users cannot sign in to the admin.')),
                    ]),
                ]),
                Group::make()->columnSpan(1)->schema([
                    Section::make()->schema([
                        Fields::image('avatar')->label('Profile photo')->avatar()->imageEditor()
                            ->helperText(__('Optional. Square photo.')),
                        Placeholder::make('last_login_at')
                            ->label('Last login')
                            ->content(fn (?User $record): string => $record?->last_login_at?->diffForHumans() ?? __('Never')),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('')
                    ->collection('avatar')
                    ->conversion('thumb')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable()->weight('bold')
                    ->description(fn (User $record): string => $record->email),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __(str($state)->headline()->toString())),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->disabled(fn (User $record): bool => self::isSelf($record) || Gate::denies('update', $record)),
                Tables\Columns\TextColumn::make('last_login_at')->label('Last login')->since()->sortable()->placeholder(__('Never')),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\SelectFilter::make('roles')->label('Role')->relationship('roles', 'name')->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    // Filament has no per-record bulk authorization: skip yourself and anyone the policy protects.
                    ->using(fn (Collection $records) => $records
                        ->filter(fn (User $record): bool => Gate::allows('delete', $record))
                        ->each->delete()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
