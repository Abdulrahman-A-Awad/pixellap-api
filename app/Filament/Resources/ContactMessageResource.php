<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactMessageResource\Pages;
use App\Filament\Support\TableKit;
use App\Models\ContactMessage;
use App\Models\ServiceCategory;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;

/**
 * Leads from the website contact form. Visitor fields are read-only;
 * staff manage status, assignee and internal notes.
 */
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?int $navigationSort = 1;

    /** Global search (topbar): name, email, company, phone. */
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'company', 'phone'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('Email') => $record->email,
            __('Received') => $record->created_at?->diffForHumans(),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return null; // top level, under the Dashboard
    }

    public static function getModelLabel(): string
    {
        return __('Message');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Messages');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::query()->where('status', ContactStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['serviceCategory', 'assignee'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** Digits only, for wa.me links. */
    public static function whatsappUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits !== '' ? "https://wa.me/{$digits}" : null;
    }

    private static function setStatusAction(string $name, ContactStatus $status, string $icon): Tables\Actions\Action
    {
        return Tables\Actions\Action::make($name)
            ->label(__('Mark :status', ['status' => $status->getLabel()]))
            ->icon($icon)
            ->color($status->color())
            ->visible(fn (ContactMessage $record): bool => $record->status !== $status
                && ! $record->trashed()
                && Gate::allows('update', $record))
            ->action(function (ContactMessage $record) use ($status): void {
                $record->update(['status' => $status]);
                Notification::make()->success()->title(__('Status updated'))->send();
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (ContactMessage $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->weight('bold')
                    ->description(fn (ContactMessage $record): ?string => $record->company),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->url(fn (ContactMessage $record): string => 'mailto:'.$record->email)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('serviceCategory.title')->label('Service')->limit(30)->placeholder('—'),
                Tables\Columns\TextColumn::make('locale')->label('Language')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assignee.name')->label('Assigned to')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('Received')->since()->sortable()
                    ->dateTimeTooltip(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(ContactStatus::class),
                Tables\Filters\SelectFilter::make('service_category_id')
                    ->label('Service')
                    ->options(fn (): array => ServiceCategory::ordered()->get()
                        ->mapWithKeys(fn (ServiceCategory $c): array => [$c->id => $c->title])->all()),
                TableKit::dateRangeFilter(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                self::setStatusAction('markReplied', ContactStatus::Replied, 'heroicon-o-check'),
                self::setStatusAction('markClosed', ContactStatus::Closed, 'heroicon-o-archive-box'),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('changeStatus')
                        ->label(__('Change status'))
                        ->icon('heroicon-o-tag')
                        ->visible(fn (): bool => Gate::allows('update', new ContactMessage))
                        ->form([
                            Select::make('status')->label('Status')->options(ContactStatus::class)->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records
                                ->filter(fn (ContactMessage $record): bool => Gate::allows('update', $record))
                                ->each(fn (ContactMessage $record) => $record->update(['status' => $data['status']]));
                            Notification::make()->success()->title(__('Status updated'))->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->columns(3)->schema([
            Section::make(__('Message'))
                ->columnSpan(2)
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label('Name')->weight('bold'),
                    TextEntry::make('company')->label('Company')->placeholder('—'),
                    TextEntry::make('email')
                        ->label('Email')
                        ->url(fn (ContactMessage $record): string => 'mailto:'.$record->email)
                        ->color('primary')
                        ->copyable(),
                    TextEntry::make('phone')
                        ->label('Phone')
                        ->placeholder('—')
                        ->url(fn (ContactMessage $record): ?string => $record->phone ? 'tel:'.preg_replace('/[^\d+]/', '', $record->phone) : null)
                        ->color('primary')
                        ->suffixAction(
                            Action::make('whatsapp')
                                ->icon('heroicon-o-chat-bubble-left-right')
                                ->tooltip('WhatsApp')
                                ->url(fn (ContactMessage $record): ?string => self::whatsappUrl($record->phone), shouldOpenInNewTab: true)
                                ->visible(fn (ContactMessage $record): bool => filled($record->phone)),
                        ),
                    TextEntry::make('serviceCategory.title')->label('Service')->placeholder('—'),
                    TextEntry::make('locale')->label('Language')->badge()
                        ->formatStateUsing(fn (string $state): string => $state === 'ar' ? 'العربية' : 'English'),
                    TextEntry::make('message')
                        ->label('Message')
                        ->columnSpanFull()
                        ->extraAttributes(['dir' => 'auto', 'style' => 'white-space: pre-line']),
                ]),
            Section::make(__('Details'))
                ->columnSpan(1)
                ->schema([
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('created_at')->label('Received')->dateTime(),
                    TextEntry::make('ip')->label('IP address')->placeholder('—'),
                    TextEntry::make('user_agent')->label('Browser')->placeholder('—')->size('xs')->color('gray'),
                    TextEntry::make('deleted_at')->label('Deleted')->dateTime()->visible(fn (ContactMessage $record): bool => $record->trashed()),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'view' => Pages\ViewContactMessage::route('/{record}'),
        ];
    }
}
