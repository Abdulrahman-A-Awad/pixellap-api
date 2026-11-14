<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Production bootstrap (no seeded default admin): creates a super admin interactively.
 */
class CreateSuperAdmin extends Command
{
    public const MIN_PASSWORD = 12;

    protected $signature = 'pixellap:create-super-admin';

    protected $description = 'Create a super admin account (interactive)';

    public function handle(): int
    {
        $name = text(
            label: 'Name',
            required: true,
            validate: fn (string $value): ?string => mb_strlen(trim($value)) > 255 ? 'The name is too long.' : null,
        );

        $email = text(
            label: 'Email',
            required: true,
            validate: function (string $value): ?string {
                $value = mb_strtolower(trim($value));

                return match (true) {
                    filter_var($value, FILTER_VALIDATE_EMAIL) === false => 'Enter a valid email address.',
                    User::where('email', $value)->exists() => 'A user with this email already exists.',
                    default => null,
                };
            },
        );

        $password = password(
            label: 'Password',
            required: true,
            validate: fn (string $value): ?string => match (true) {
                mb_strlen($value) < self::MIN_PASSWORD => 'The password must be at least '.self::MIN_PASSWORD.' characters.',
                str_contains(mb_strtolower($value), 'password') => 'Choose a less guessable password.',
                default => null,
            },
            hint: 'At least '.self::MIN_PASSWORD.' characters.',
        );

        password(
            label: 'Confirm password',
            required: true,
            validate: fn (string $value): ?string => $value !== $password ? 'The passwords do not match.' : null,
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate(AppServiceProvider::SUPER_ADMIN_ROLE, 'web');

        $user = User::create([
            'name' => trim($name),
            'email' => mb_strtolower(trim($email)),
            'password' => $password,
            'is_active' => true,
        ]);
        $user->assignRole(AppServiceProvider::SUPER_ADMIN_ROLE);

        $this->components->info("Super admin {$user->email} created. Sign in at ".rtrim((string) config('app.url'), '/').'/admin');

        return self::SUCCESS;
    }
}
