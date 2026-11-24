<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pre-launch / post-deploy sanity check. Exit code 1 (and a list of every problem) when the
 * installation is not safe to run in production. Warnings are printed but do not fail.
 */
class CheckProduction extends Command
{
    public const MIN_SECRET_LENGTH = 32;

    public const DEFAULT_ADMIN_EMAIL = 'admin@pixellap.test';

    public const DEFAULT_PASSWORD = 'password';

    protected $signature = 'pixellap:check-production';

    protected $description = 'Fail loudly if this installation is not ready for production';

    /** @var list<string> */
    private array $problems = [];

    /** @var list<string> */
    private array $warnings = [];

    public function handle(): int
    {
        // The command instance is reused when called twice in one process (tests, Artisan::call).
        $this->problems = $this->warnings = [];

        $this->checkConfig();
        $this->checkDatabase();
        $this->checkFilesystem();

        foreach ($this->warnings as $warning) {
            $this->components->warn($warning);
        }

        if ($this->problems !== []) {
            $this->newLine();
            $this->components->error(count($this->problems).' problem(s) block production:');
            foreach ($this->problems as $problem) {
                $this->line("  <fg=red>✗</> {$problem}");
            }
            $this->newLine();

            return self::FAILURE;
        }

        $this->components->info('Production checks passed.');

        return self::SUCCESS;
    }

    private function checkConfig(): void
    {
        if (config('app.debug')) {
            $this->problems[] = 'APP_DEBUG is true (stack traces and secrets are shown to visitors).';
        }

        if (blank(config('app.key'))) {
            $this->problems[] = 'APP_KEY is empty (run: php artisan key:generate).';
        }

        foreach (['API_SITE_KEY' => 'pixellap.api_site_key', 'REVALIDATE_SECRET' => 'pixellap.revalidate_secret'] as $env => $key) {
            $value = (string) config($key);
            if ($value === '') {
                $this->problems[] = "{$env} is empty.";
            } elseif (strlen($value) < self::MIN_SECRET_LENGTH) {
                $this->problems[] = "{$env} is shorter than ".self::MIN_SECRET_LENGTH.' characters (generate one with: openssl rand -hex 32).';
            }
        }

        if (! app()->isProduction()) {
            $this->warnings[] = 'APP_ENV is "'.app()->environment().'", not "production".';
        }
        if (! str_starts_with((string) config('app.url'), 'https://')) {
            $this->warnings[] = 'APP_URL does not start with https://.';
        }
        if (config('session.secure') !== true) {
            $this->warnings[] = 'SESSION_SECURE_COOKIE is not true.';
        }
        $this->checkMail();

        if (blank(config('pixellap.frontend_revalidate_url'))) {
            $this->warnings[] = 'FRONTEND_REVALIDATE_URL is empty: admin edits will not refresh the website.';
        }
    }

    /** Contact leads must reach a real inbox: no log mailer, no CHANGE_ME placeholders, valid addresses. */
    private function checkMail(): void
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warnings[] = "MAIL_MAILER is \"{$mailer}\": contact emails are not delivered.";

            return;
        }

        if ($mailer === 'smtp') {
            foreach (['MAIL_HOST' => 'host', 'MAIL_USERNAME' => 'username', 'MAIL_PASSWORD' => 'password'] as $env => $key) {
                $value = (string) config("mail.mailers.smtp.{$key}");
                if ($value === '' || str_contains($value, 'CHANGE_ME')) {
                    $this->problems[] = "{$env} is empty or still a CHANGE_ME placeholder.";
                }
            }
            $scheme = config('mail.mailers.smtp.scheme');
            if ((int) config('mail.mailers.smtp.port') === 465 && $scheme !== 'smtps') {
                $this->problems[] = 'MAIL_PORT is 465 but MAIL_SCHEME is not "smtps" (implicit TLS).';
            }
        }

        foreach (['MAIL_FROM_ADDRESS' => config('mail.from.address'), 'CONTACT_NOTIFY_EMAIL' => config('pixellap.contact_notify_email')] as $env => $value) {
            if (! is_string($value) || str_contains($value, 'CHANGE_ME') || ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->problems[] = "{$env} is not a real email address.";
            }
        }
    }

    private function checkDatabase(): void
    {
        try {
            if (User::where('email', self::DEFAULT_ADMIN_EMAIL)->exists()) {
                $this->problems[] = 'The default admin '.self::DEFAULT_ADMIN_EMAIL.' still exists (delete it in Admin → Users).';
            }

            $weak = User::query()->get(['id', 'email', 'password'])
                ->filter(fn (User $user): bool => $user->password !== null && Hash::check(self::DEFAULT_PASSWORD, $user->password))
                ->pluck('email');
            foreach ($weak as $email) {
                $this->problems[] = "User {$email} still has the password \"".self::DEFAULT_PASSWORD.'".';
            }

            $todo = Setting::query()->get(['key', 'value'])
                ->filter(fn (Setting $setting): bool => str_contains((string) json_encode($setting->value, JSON_UNESCAPED_UNICODE), 'TODO:'))
                ->pluck('key');
            if ($todo->isNotEmpty()) {
                $this->problems[] = 'Settings still contain "TODO:" placeholders: '.$todo->implode(', ').' (Admin → Settings).';
            }

            if (config('queue.default') === 'database') {
                foreach ([config('queue.connections.database.table', 'jobs'), config('queue.failed.table', 'failed_jobs')] as $table) {
                    if (! Schema::hasTable((string) $table)) {
                        $this->problems[] = "Queue table \"{$table}\" is missing (run: php artisan migrate --force).";
                    }
                }
            }
        } catch (Throwable $e) {
            $this->problems[] = 'Database check failed: '.$e->getMessage();
        }
    }

    private function checkFilesystem(): void
    {
        // Resolve both sides: catches a missing link and one pointing somewhere else.
        $link = realpath(public_path('storage'));

        if ($link === false || $link !== realpath(storage_path('app/public'))) {
            $this->problems[] = 'The public storage link is missing (run: php artisan storage:link).';
        }

        if (! is_writable(storage_path('logs')) || ! is_writable(storage_path('app/public'))) {
            $this->problems[] = 'storage/ is not writable by this user.';
        }
    }
}
