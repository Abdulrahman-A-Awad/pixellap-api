<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceCategoryResource\RelationManagers;

use App\Filament\Concerns\RevalidatesOnReorder;
use App\Filament\Support\Fields;
use App\Filament\Support\TableKit;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** FAQs of a service category. English | Arabic side by side (no language switcher). */
class FaqsRelationManager extends RelationManager
{
    use RevalidatesOnReorder;

    protected static string $relationship = 'faqs';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('FAQs');
    }

    public static function getModelLabel(): string
    {
        return __('FAQ');
    }

    public static function getPluralModelLabel(): string
    {
        return __('FAQs');
    }

    public function form(Form $form): Form
    {
        return $form->columns(1)->schema([
            Fields::bilingual('question', 'Question', required: true),
            Fields::bilingual('answer', 'Answer', 'rich', __('Shown when the visitor opens the question.'), required: true),
            Fields::visible(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                ...TableKit::bilingualColumns('question', 'Question'),
                TableKit::visibleToggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
