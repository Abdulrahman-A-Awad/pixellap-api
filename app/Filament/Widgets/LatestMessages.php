<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessageResource;
use App\Filament\Support\PermissionMatrix;
use App\Models\ContactMessage;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** The 5 most recent contact messages; a row opens the message. */
class LatestMessages extends TableWidget
{
    public const LIMIT = 5;

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return PermissionMatrix::can('messages');
    }

    /** Rendered with the page: a lazy placeholder showed as an empty card. */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): string
    {
        return __('Latest messages');
    }

    public function table(Table $table): Table
    {
        return $table
            // Limit through the keys (a ->limit() on the table query is dropped by Filament's count/sort).
            ->query(fn () => ContactMessage::query()
                ->with('serviceCategory')
                ->whereKey(ContactMessage::query()->latest()->latest('id')->limit(self::LIMIT)->pluck('id')))
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->recordUrl(fn (ContactMessage $record): string => ContactMessageResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('name')->label('Name')->weight('bold'),
                Tables\Columns\TextColumn::make('serviceCategory.title')->label('Service')->limit(30)->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Received')->since(),
            ])
            ->emptyStateIcon('heroicon-o-inbox')
            ->emptyStateHeading(__('No messages yet'))
            ->emptyStateDescription(__('Messages sent from the website’s contact form will appear here.'))
            ->headerActions([
                Tables\Actions\Action::make('all')
                    ->label(__('View all'))
                    ->url(ContactMessageResource::getUrl('index'))
                    ->link(),
            ]);
    }
}
