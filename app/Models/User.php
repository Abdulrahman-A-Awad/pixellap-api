<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasWebpMedia;
use App\Providers\AppServiceProvider;
use App\Support\MimeTypes;
use App\Support\WebsiteReadiness;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasWebpMedia, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'admin_locale',
    ];

    /**
     * Mirror DB defaults so freshly created models are correct before a refresh.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'admin_locale' => 'en',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The dashboard checklist flags the default admin account.
        static::saved(fn () => WebsiteReadiness::forget());
        static::deleted(fn () => WebsiteReadiness::forget());
    }

    /** Active staff with at least one role may use the admin panel. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->roles()->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(AppServiceProvider::SUPER_ADMIN_ROLE);
    }

    /** @return HasMany<ContactMessage, $this> */
    public function assignedContactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'assigned_to');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile()->acceptsMimeTypes(MimeTypes::IMAGES);
    }

    protected function webpCollections(): array
    {
        return ['avatar'];
    }
}
