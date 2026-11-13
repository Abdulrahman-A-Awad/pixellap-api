# Pixellap API — Project Rules

Bilingual (EN/AR) CMS-driven company website for a UAE media agency.
This repo is the Laravel API + Filament admin. A separate Next.js app (`../pixellap-web`) consumes the API.

## Stack

- Laravel 12, PHP 8.2+, MySQL (XAMPP MariaDB locally, database `pixellap`).
- Filament v3 admin panel at `/admin` (panel id `admin`).
- Public **read-only** API at `/api/v1` (`routes/api.php`), protected by `site.key` and localized by `locale` middleware.
- Custom settings live in `config/pixellap.php`. Never call `env()` outside `config/`.

## Content rules

- **Translations:** every user-facing text field is translatable JSON `{en, ar}` via spatie/laravel-translatable
  (`use HasTranslations;` + `public array $translatable = [...]`). Never hardcode content strings in code.
- **Ordering & visibility:** every content table has `sort` (int, default 0) and `is_visible` (bool, default true).
  The public API returns only visible rows, ordered by `sort`.
- **Media:** images/videos go through spatie/laravel-medialibrary collections — never path columns.
  Register webp conversions on every media model: `thumb` (400w), `md` (1024w), `lg` (1920w).
- **API output:** always via API Resources. Resources return the **current locale string only**, never the full translation JSON object.
- **Slugs:** English-only, shared by both locales.
- **Activity log:** every content model logs activity (spatie/laravel-activitylog, `logOnlyDirty()`).
- **Permissions:** Filament Shield (spatie/laravel-permission). Super Admin bypasses all checks via `Gate::before`.
- **Cache revalidation:** after any content save/delete, an observer calls the frontend revalidate webhook
  (`config('pixellap.frontend_revalidate_url')`, signed with `config('pixellap.revalidate_secret')`) with cache tags.
  (Implemented in a later step.)

## Code style

- `declare(strict_types=1);` in every PHP file we author.
- Validation through Form Requests.
- Controllers only orchestrate — no business logic in controllers.

## Brand colors

| Name     | Hex       |
|----------|-----------|
| royal    | `#3A119E` |
| iris     | `#612FE2` (Filament primary) |
| lavender | `#8152F8` |
| pearl    | `#DFEAFB` |
| black    | `#000000` |

## Local notes

- Run all commands from inside `pixellap-api/`.
- In `APP_ENV=local`, GET API requests without an `X-Site-Key` header are allowed (browser testing). A header that is sent must be valid.
- `php artisan db:show` fails on XAMPP MariaDB (`performance_schema.session_status` missing) — harmless; use `php artisan migrate:status` instead.
