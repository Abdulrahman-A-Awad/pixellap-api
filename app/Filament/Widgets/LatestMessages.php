<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestMessages extends TableWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): string
    {
        return __('Latest messages');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ContactMessage::query()->with('serviceCategory')->latest()->limit(5))
            ->paginated(false)
            ->recordUrl(fn (ContactMessage $record): string => ContactMessageResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('name')->label('Name')->weight('bold'),
                Tables\Columns\TextColumn::make('email')->label('Email'),
                Tables\Columns\TextColumn::make('serviceCategory.title')->label('Service')->limit(30)->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Received')->since(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('all')
                    ->label(__('View all'))
                    ->url(ContactMessageResource::getUrl('index'))
                    ->link(),
            ]);
    }
}
