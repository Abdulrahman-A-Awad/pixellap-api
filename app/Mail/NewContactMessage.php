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
use Illuminate\Support\Str;

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

    /**
     * Sent from MAIL_FROM_ADDRESS (the team mailbox, so SPF/DKIM align); Reply-To is the visitor,
     * so answering from the inbox goes straight to them.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine(),
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
        );
    }

    /** "New inquiry — {name} — {service}" (service omitted when none was chosen; names keep their script). */
    public function subjectLine(): string
    {
        $this->contactMessage->loadMissing('serviceCategory');

        $parts = [
            'New inquiry',
            Str::limit(Str::squish($this->contactMessage->name), 60),
            $this->contactMessage->serviceCategory?->getTranslation('title', 'en'),
        ];

        return implode(' — ', array_filter($parts, 'filled'));
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
