<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Resources\ContactMessageResource;
use App\Http\Middleware\SetAdminLocale;
use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Stores a contact-form lead and alerts the team (queued email + admin bell notification).
 */
final class ContactInbox
{
    /** Roles that receive a database notification for every new lead. */
    public const NOTIFY_ROLES = ['admin', 'sales'];

    /**
     * @param  array{name: string, email: string, phone?: ?string, company?: ?string, service?: ?string, message: string, locale?: ?string}  $data
     */
    public function receive(array $data, ?string $ip, ?string $userAgent): ContactMessage
    {
        $categoryId = filled($data['service'] ?? null)
            ? ServiceCategory::query()->where('slug', $data['service'])->value('id')
            : null;

        $message = ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'service_category_id' => $categoryId,
            'message' => $data['message'],
            'locale' => $data['locale'] ?? app()->getLocale(),
            'ip' => $ip,
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 1000) : null,
        ]);

        $adminUrl = ContactMessageResource::getUrl('view', ['record' => $message], panel: 'admin');

        $this->mailTeam($message, $adminUrl);
        $this->notifyStaff($message, $adminUrl);

        return $message;
    }

    /** Settings email if it is a real address, otherwise config('pixellap.contact_notify_email'). */
    public function notifyEmail(): ?string
    {
        foreach ([Setting::get('email'), config('pixellap.contact_notify_email')] as $candidate) {
            if (is_string($candidate) && filter_var(trim($candidate), FILTER_VALIDATE_EMAIL)) {
                return trim($candidate);
            }
        }

        return null;
    }

    private function mailTeam(ContactMessage $message, string $adminUrl): void
    {
        $to = $this->notifyEmail();

        if ($to === null) {
            Log::warning('Contact message received but no notification email is configured.', ['id' => $message->id]);

            return;
        }

        Mail::to($to)->locale('en')->queue(new NewContactMessage($message, $adminUrl));
    }

    private function notifyStaff(ContactMessage $message, string $adminUrl): void
    {
        $users = User::query()->role(self::NOTIFY_ROLES)->where('is_active', true)->get();

        foreach ($users as $user) {
            // Written in each recipient's admin language.
            $locale = SetAdminLocale::current($user);

            Notification::make()
                ->title(__('New contact message', [], $locale))
                ->body(__('From :name (:email)', ['name' => $message->name, 'email' => $message->email], $locale))
                ->icon('heroicon-o-inbox')
                ->info()
                ->actions([
                    Action::make('view')
                        ->label(__('View message', [], $locale))
                        ->url($adminUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($user);
        }
    }
}
