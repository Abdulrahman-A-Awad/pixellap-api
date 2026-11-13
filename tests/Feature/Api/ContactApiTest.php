<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use App\Models\ServiceCategory;
use App\Models\User;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        config(['pixellap.contact_notify_email' => 'leads@pixellap.test']);
    }

    /** @return array<string, string> */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Layla Ahmed',
            'email' => 'layla@example.com',
            'phone' => '+971 (50) 123-4567',
            'company' => 'Desert Bloom LLC',
            'service' => ServiceCategory::query()->ordered()->firstOrFail()->slug,
            'message' => 'We would like a new brand identity and website.',
            'locale' => 'en',
            ...$overrides,
        ];
    }

    public function test_valid_submission_is_stored_mailed_and_notified(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $sales = User::factory()->create(['admin_locale' => 'ar']);
        $sales->assignRole('sales');
        $editor = User::factory()->create();
        $editor->assignRole('content_editor');

        $this->postJson('/api/v1/contact', $this->payload(['locale' => 'ar']), ['User-Agent' => 'PHPUnit'])
            ->assertCreated()
            ->assertJsonPath('data.message', 'شكراً لتواصلك معنا. تم إرسال رسالتك وسنتواصل معك قريباً.')
            ->assertJsonPath('meta.locale', 'ar');

        $message = ContactMessage::query()->sole();
        $this->assertSame('Layla Ahmed', $message->name);
        $this->assertSame('ar', $message->locale);
        $this->assertSame(ServiceCategory::query()->ordered()->first()->id, $message->service_category_id);
        $this->assertSame('PHPUnit', $message->user_agent);
        $this->assertNotNull($message->ip);

        Mail::assertQueued(NewContactMessage::class, function (NewContactMessage $mail) use ($message): bool {
            return $mail->hasTo('leads@pixellap.test')
                && $mail->contactMessage->is($message)
                && str_contains($mail->adminUrl, "/admin/contact-messages/{$message->id}")
                && $mail->locale === 'en';
        });

        Notification::assertSentTo([$admin, $sales], DatabaseNotification::class);
        Notification::assertNotSentTo($editor, DatabaseNotification::class);
    }

    public function test_english_success_message(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/contact', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.message', 'Thank you. Your message has been sent and we will be in touch shortly.');
    }

    public function test_mail_renders(): void
    {
        $message = ContactMessage::create([...$this->payload(), 'service_category_id' => null]);

        $html = (new NewContactMessage($message, 'http://localhost/admin/contact-messages/1'))->render();

        $this->assertStringContainsString('Layla Ahmed', $html);
        $this->assertStringContainsString('http://localhost/admin/contact-messages/1', $html);
    }

    public function test_honeypot_returns_201_without_storing(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/contact', ['website' => 'http://spam.example', 'name' => ''])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['message'], 'meta']);

        $this->assertSame(0, ContactMessage::query()->count());
        Mail::assertNothingQueued();
    }

    public function test_invalid_submission_returns_arabic_messages(): void
    {
        $this->postJson('/api/v1/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'phone' => 'call me',
            'service' => 'unknown-service',
            'message' => 'short',
            'locale' => 'ar',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'service', 'message'])
            ->assertJsonPath('errors.name.0', 'حقل الاسم مطلوب.')
            ->assertJsonPath('errors.email.0', 'يُرجى إدخال بريد إلكتروني صحيح.')
            ->assertJsonPath('errors.message.0', 'يُرجى كتابة المزيد من التفاصيل عن مشروعك، بما لا يقل عن 10 أحرف.');

        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_hidden_category_is_rejected(): void
    {
        $category = ServiceCategory::query()->ordered()->firstOrFail();
        $category->updateQuietly(['is_visible' => false]);

        $this->postJson('/api/v1/contact', $this->payload(['service' => $category->slug]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service']);
    }

    public function test_sixth_request_in_a_minute_is_throttled(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/contact', $this->payload())->assertCreated();
        }

        $this->postJson('/api/v1/contact', $this->payload())
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_read_requests_do_not_count_towards_the_contact_limit(): void
    {
        Mail::fake();
        config(['pixellap.api_site_key' => 'k']);

        for ($i = 0; $i < 6; $i++) {
            $this->getJson('/api/v1/site', ['X-Site-Key' => 'k'])->assertOk();
        }

        $this->postJson('/api/v1/contact', $this->payload())->assertCreated();
    }
}
