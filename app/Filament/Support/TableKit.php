<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared table building blocks for admin resources.
 */
final class TableKit
{
    /** Inline visibility toggle — read-only for users who cannot update the row. */
    public static function visibleToggle(): ToggleColumn
    {
        return ToggleColumn::make('is_visible')
            ->label('Visible')
            ->disabled(fn (Model $record): bool => ! (auth()->user()?->can('update', $record) ?? false));
    }

    /**
     * Both languages of a translatable attribute: `{name}` shows English, `{name}_ar` shows Arabic
     * (RTL) — or a warning "AR missing" badge when it is empty. No language switcher needed.
     *
     * @return array{0: TextColumn, 1: TextColumn}
     */
    public static function bilingualColumns(string $name, string $label, int $limit = 70): array
    {
        $translation = fn (Model $record, string $locale): string => method_exists($record, 'getTranslation')
            ? trim(strip_tags((string) $record->getTranslation($name, $locale, false)))
            : '';
        // "AR missing" only when there is English text to translate (optional fields may be empty in both).
        $missing = fn (Model $record): bool => $translation($record, 'ar') === '' && $translation($record, 'en') !== '';

        return [
            TextColumn::make($name)
                ->label(__($label).' (EN)')
                ->state(fn (Model $record): string => $translation($record, 'en'))
                ->placeholder('—')
                ->limit($limit)
                ->wrap()
                ->weight('bold'),
            TextColumn::make("{$name}_ar")
                ->label(__($label).' (AR)')
                ->state(fn (Model $record): string => $missing($record) ? __('AR missing') : $translation($record, 'ar'))
                ->placeholder('—')
                ->badge(fn (Model $record): bool => $missing($record))
                ->color(fn (Model $record): ?string => $missing($record) ? 'warning' : null)
                ->icon(fn (Model $record): ?string => $missing($record) ? 'heroicon-m-exclamation-triangle' : null)
                ->extraAttributes(fn (Model $record): array => $missing($record) ? [] : ['dir' => 'rtl', 'lang' => 'ar'])
                ->limit($limit)
                ->wrap(),
        ];
    }

    public static function visibleFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_visible')->label('Visible');
    }

    /** created_at between two dates. */
    public static function dateRangeFilter(string $column = 'created_at'): Filter
    {
        return Filter::make('date_range')
            ->label('Date')
            ->form([
                DatePicker::make('from')->label('From'),
                DatePicker::make('until')->label('Until'),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate($column, '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate($column, '<=', $date)))
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['from'] ?? null) {
                    $indicators[] = __('From').' '.$data['from'];
                }
                if ($data['until'] ?? null) {
                    $indicators[] = __('Until').' '.$data['until'];
                }

                return $indicators;
            });
    }
}
