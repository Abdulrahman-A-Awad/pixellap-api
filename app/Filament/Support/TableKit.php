<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms\Components\DatePicker;
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
