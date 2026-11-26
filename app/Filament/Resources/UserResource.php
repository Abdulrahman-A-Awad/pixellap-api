<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Support\Fields;
use App\Filament\Support\NavGroup;
use App\Filament\Support\PermissionMatrix;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Closure;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
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

    /** 16 characters: letters, numbers and symbols. */
    public static function generatePassword(): string
    {
        return Str::password(16);
    }

    /** One line per role this user may grant: "Admin — everything except managing roles." */
    private static function roleDescriptions(): HtmlString
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->when(! self::actor()?->isSuperAdmin(), fn (Builder $q) => $q->where('name', '!=', AppServiceProvider::SUPER_ADMIN_ROLE))
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role): string => '<strong>'.e(PermissionMatrix::roleLabel($role->name)).'</strong> — '.e(PermissionMatrix::roleDescription($role) ?? '—'))
            ->pipe(fn ($lines) => new HtmlString($lines->implode('<br>')));
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
                    // No browser autofill: the create form must open empty (not with the admin's own saved login).
                    Section::make(__('Account'))
                        ->description(__('Who this person is and how they sign in.'))
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')->label('Name')->required()->maxLength(255)
                                ->autocomplete('off')
                                ->extraInputAttributes(['data-lpignore' => 'true', 'data-1p-ignore' => 'true'])
                                ->helperText(__('Shown in the admin only.')),
                            TextInput::make('email')->label('Email')->email()->required()->maxLength(255)
                                ->autocomplete('off')
                                ->extraInputAttributes(['data-lpignore' => 'true', 'data-1p-ignore' => 'true'])
                                ->helperText(__('Used to sign in.'))
                                ->unique(ignoreRecord: true),
                            TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->revealable()
                                ->autocomplete('new-password')
                                ->extraInputAttributes(['data-lpignore' => 'true', 'data-1p-ignore' => 'true'])
                                ->minLength(8)
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->dehydrated(fn (?string $state): bool => filled($state)) // hashed by the model cast
                                ->columnSpanFull()
                                ->suffixActions([
                                    FormAction::make('generatePassword')
                                        ->label(__('Generate password'))
                                        ->tooltip(__('Generate password'))
                                        ->icon('heroicon-m-sparkles')
                                        ->action(function (Set $set): void {
                                            $set('password', self::generatePassword());
                                            Notification::make()->success()
                                                ->title(__('Password generated'))
                                                ->body(__('Copy it now and send it to the person safely. It is not shown again after saving.'))
                                                ->send();
                                        }),
                                    FormAction::make('copyPassword')
                                        ->label(__('Copy password'))
                                        ->tooltip(__('Copy password'))
                                        ->icon('heroicon-m-clipboard-document')
                                        ->alpineClickHandler(fn (): string => sprintf(
                                            "const v = \$wire.get('data.password') ?? ''; if (v) { window.navigator.clipboard.writeText(v); \$tooltip(%s, { theme: \$store.theme, timeout: 2000 }) }",
                                            json_encode(__('Copied'), JSON_UNESCAPED_UNICODE),
                                        )),
                                ])
                                ->helperText(fn (string $operation): string => $operation === 'edit'
                                    ? __('Leave empty to keep the current password.')
                                    : __('At least 8 characters. Use "Generate password" for a strong one.')),
                        ]),
                    Section::make(__('Access'))->description(__('What this person can do in the admin.'))->columns(1)->schema([
                        Select::make('roles')
                            ->label('Roles')
                            ->helperText(fn (): HtmlString => self::roleDescriptions())
                            ->relationship(
                                'roles',
                                'name',
                                modifyQueryUsing: fn (Builder $query) => self::actor()?->isSuperAdmin()
                                    ? $query
                                    : $query->where('name', '!=', AppServiceProvider::SUPER_ADMIN_ROLE),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => PermissionMatrix::roleLabel($record->name))
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
                    ->formatStateUsing(fn (string $state): string => PermissionMatrix::roleLabel($state)),
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
