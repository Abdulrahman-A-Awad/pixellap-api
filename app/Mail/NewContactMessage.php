<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Team alert for a new contact-form lead (English, markdown).
 */
class NewContactMessage extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ContactMessage $contactMessage,
        public readonly string $adminUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New contact message from {$this->contactMessage->name}",
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
        );
    }

    public function content(): Content
    {
        $this->contactMessage->loadMissing('serviceCategory');

        return new Content(markdown: 'mail.contact.new-message', with: [
            'msg' => $this->contactMessage,
            'service' => $this->contactMessage->serviceCategory?->getTranslation('title', 'en'),
            'adminUrl' => $this->adminUrl,
        ]);
    }
}
