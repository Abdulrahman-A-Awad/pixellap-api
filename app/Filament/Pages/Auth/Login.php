<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Filament login with an extra limiter: 5 attempts per minute per email + IP.
 * (Filament's own limiter — 5 per minute per IP — still applies on top.)
 */
class Login extends BaseLogin
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public function authenticate(): ?LoginResponse
    {
        $ip = (string) request()->ip();
        $key = self::throttleKey((string) ($this->data['email'] ?? ''), $ip);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->getRateLimitedNotification(
                new TooManyRequestsException(static::class, 'authenticate', $ip, RateLimiter::availableIn($key)),
            )?->send();

            return null;
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        $response = parent::authenticate();

        if ($response !== null) {
            RateLimiter::clear($key);
        }

        return $response;
    }

    public static function throttleKey(string $email, string $ip): string
    {
        return 'admin-login:'.sha1(Str::lower(trim($email)).'|'.$ip);
    }
}
