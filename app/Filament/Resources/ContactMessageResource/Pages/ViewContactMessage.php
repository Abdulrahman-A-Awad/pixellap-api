<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read-only message details (infolist) + a "Follow-up" form for staff
 * (status, assignee, internal notes).
 *
 * @property Form $triageForm
 */
class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected static string $view = 'filament.resources.contact-message-resource.pages.view-contact-message';

    /** @var array<string, mixed>|null */
    public ?array $triage = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var ContactMessage $message */
        $message = $this->getRecord();

        $this->triageForm->fill([
            'status' => $message->status->value,
            'assigned_to' => $message->assigned_to,
            'internal_notes' => $message->internal_notes,
        ]);
    }

    public function canTriage(): bool
    {
        return ContactMessageResource::canEdit($this->getRecord()) && ! $this->getRecord()->trashed();
    }

    protected function getForms(): array
    {
        return [
            ...parent::getForms(),
            'triageForm' => $this->triageForm($this->makeForm()->statePath('triage')->model($this->getRecord())),
        ];
    }

    public function triageForm(Form $form): Form
    {
        return $form
            ->disabled(fn (): bool => ! $this->canTriage())
            ->schema([
                Section::make(__('Follow-up'))
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(ContactStatus::class)
                            ->required()
                            ->native(false),
                        Select::make('assigned_to')
                            ->label('Assigned to')
                            ->options(fn (): array => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false),
                        Textarea::make('internal_notes')
                            ->label('Internal notes')
                            ->helperText(__('Only visible to staff.'))
                            ->rows(4)
                            ->extraInputAttributes(['dir' => 'auto'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function saveTriage(): void
    {
        abort_unless($this->canTriage(), 403);

        $this->getRecord()->update($this->triageForm->getState());

        Notification::make()->success()->title(__('Saved'))->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label(__('Reply by email'))
                ->icon('heroicon-o-envelope')
                ->url(fn (): string => 'mailto:'.$this->getRecord()->email),
            Actions\Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn (): ?string => ContactMessageResource::whatsappUrl($this->getRecord()->phone), shouldOpenInNewTab: true)
                ->visible(fn (): bool => filled($this->getRecord()->phone)),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
