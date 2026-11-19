<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Filament\Support\NavGroup;
use App\Filament\Support\TableKit;
use App\Models\User;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-only audit trail from spatie/laravel-activitylog.
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'activity-log';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __(NavGroup::ACCESS);
    }

    /** The "Access" group is shown to super admins only (access rules stay in the policy). */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isSuperAdmin() && parent::shouldRegisterNavigation();
    }

    public static function getModelLabel(): string
    {
        return __('Activity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Activity log');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /** "attribute: old → new" lines from the logged properties. */
    public static function changes(Activity $activity): array
    {
        $new = (array) ($activity->properties['attributes'] ?? []);
        $old = (array) ($activity->properties['old'] ?? []);

        $format = fn (mixed $value): string => match (true) {
            $value === null => '∅',
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => (string) $value,
        };

        return collect(array_unique([...array_keys($new), ...array_keys($old)]))
            ->map(fn (string $key): string => array_key_exists($key, $old)
                ? "{$key}: {$format($old[$key])} → {$format($new[$key] ?? null)}"
                : "{$key}: {$format($new[$key] ?? null)}")
            ->values()
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('When')->since()->sortable()->dateTimeTooltip(),
                Tables\Columns\TextColumn::make('causer.name')->label('By')->placeholder(__('System')),
                Tables\Columns\TextColumn::make('event')->label('Event')->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Item')
                    ->formatStateUsing(fn (?string $state, Activity $record): string => class_basename((string) $state).' #'.$record->subject_id),
                Tables\Columns\TextColumn::make('log_name')->label('Log')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('By')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Item type')
                    ->options(fn (): array => Activity::query()->distinct()->whereNotNull('subject_type')->pluck('subject_type')
                        ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])->sort()->all()),
                TableKit::dateRangeFilter(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()->columns(3)->schema([
                TextEntry::make('created_at')->label('When')->dateTime(),
                TextEntry::make('causer.name')->label('By')->placeholder(__('System')),
                TextEntry::make('event')->label('Event')->badge(),
                TextEntry::make('subject_type')->label('Item')
                    ->formatStateUsing(fn (?string $state, Activity $record): string => class_basename((string) $state).' #'.$record->subject_id),
                TextEntry::make('log_name')->label('Log')->badge()->color('gray'),
                TextEntry::make('description')->label('Description'),
            ]),
            Section::make(__('Changes'))->schema([
                TextEntry::make('changes')
                    ->hiddenLabel()
                    ->state(fn (Activity $record): array => self::changes($record))
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->fontFamily('mono')
                    ->size('sm')
                    ->placeholder(__('No attribute changes recorded.')),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
