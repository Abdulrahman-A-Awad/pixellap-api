<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Support\ActivityDescriber;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    /** Typed in the confirmation modal (always this English word, in both languages). */
    public const CONFIRM_WORD = 'CONFIRM';

    public function getSubheading(): ?string
    {
        return __('Who changed what, and when. Entries older than :days days are removed automatically.', [
            'days' => (int) config('activitylog.delete_records_older_than_days'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearLog')
                ->label(__('Clear entire log'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->outlined()
                ->visible(fn (): bool => ActivityLogResource::canClear())
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalHeading(__('Clear the entire activity log?'))
                ->modalDescription(__('Every entry is deleted for good. One new entry records who cleared it.'))
                ->modalSubmitActionLabel(__('Clear log'))
                ->form([
                    TextInput::make('confirmation')
                        ->label(fn (): string => __('Type :word to continue', ['word' => self::CONFIRM_WORD]))
                        ->required()
                        ->autocomplete('off')
                        ->in([self::CONFIRM_WORD])
                        ->validationMessages(['in' => __('Type :word exactly (capital letters).', ['word' => self::CONFIRM_WORD])]),
                ])
                ->action(function (): void {
                    abort_unless(ActivityLogResource::canClear(), 403);

                    $count = Activity::query()->count();
                    DB::table((new Activity)->getTable())->truncate();

                    activity('system')
                        ->causedBy(auth()->user())
                        ->event(ActivityDescriber::CLEARED)
                        ->withProperties(['attributes' => ['removed' => $count]])
                        ->log('Log cleared');

                    Notification::make()->success()->title(__('Activity log cleared'))->send();
                }),
        ];
    }
}
