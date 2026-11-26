<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Filament\Support\NavGroup;
use App\Filament\Support\TableKit;
use App\Models\User;
use App\Support\ActivityDescriber;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
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

    /** Single rows are not deleted one by one: bulk delete (policy) or "Clear entire log" (super admin). */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return Gate::allows('deleteAny', Activity::class);
    }

    public static function canClear(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
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
            ->map(fn (string $key): string => __(Str::headline($key)).': '.(array_key_exists($key, $old)
                ? "{$format($old[$key])} → {$format($new[$key] ?? null)}"
                : $format($new[$key] ?? null)))
            ->values()
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['causer', 'subject']))
            ->columns([
                Tables\Columns\TextColumn::make('event')->label('Event')->badge()
                    ->formatStateUsing(fn (?string $state): string => ActivityDescriber::eventLabel($state))
                    ->color(fn (?string $state): string => ActivityDescriber::eventColor($state)),
                Tables\Columns\TextColumn::make('description')
                    ->label('What happened')
                    ->state(fn (Activity $record): string => ActivityDescriber::sentence($record))
                    ->description(fn (Activity $record): ?string => ActivityDescriber::area($record))
                    ->weight('medium')
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')->label('When')->since()->sortable()->dateTimeTooltip()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('area')
                    ->label('Area')
                    ->options(fn (): array => ActivityDescriber::areaOptions())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereIn('subject_type', ActivityDescriber::classesFor((string) $data['value']))
                        : $query),
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('User')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('event')
                    ->label('Event')
                    ->options(fn (): array => ActivityDescriber::eventOptions()),
                TableKit::dateRangeFilter(),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => static::canDeleteAny())
                    ->modalHeading(__('Delete selected log entries?'))
                    ->modalDescription(__('The entries are removed from the history for good. The content itself is not changed.')),
            ])
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading(__('No activity yet'))
            ->emptyStateDescription(__('Changes made in the admin will be listed here.'));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()->columns(3)->schema([
                TextEntry::make('sentence')->label('What happened')
                    ->state(fn (Activity $record): string => ActivityDescriber::sentence($record))
                    ->weight('bold')
                    ->columnSpanFull(),
                TextEntry::make('created_at')->label('When')->dateTime(),
                TextEntry::make('causer')->label('By')
                    ->state(fn (Activity $record): string => ActivityDescriber::causer($record)),
                TextEntry::make('event')->label('Event')->badge()
                    ->formatStateUsing(fn (?string $state): string => ActivityDescriber::eventLabel($state))
                    ->color(fn (?string $state): string => ActivityDescriber::eventColor($state)),
                TextEntry::make('area')->label('Area')
                    ->state(fn (Activity $record): ?string => ActivityDescriber::area($record))
                    ->placeholder('—'),
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
