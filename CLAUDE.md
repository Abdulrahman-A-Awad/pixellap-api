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

## Data layer conventions

- Shared model concerns in `app/Models/Concerns`: `IsContent` (translations + activity log + `visible()` scope),
  `IsSortable` (eloquent-sortable on `sort`, provides `ordered()`; children override `buildSortQuery()` to sort within parent),
  `HasWebpMedia` (thumb/md/lg webp conversions; list image collections in `webpCollections()`).
- Enums live in `app/Enums` (string-backed, `label()`, implement Filament `HasLabel`/`HasColor`).
- Site settings: `Setting::get('key')` (cached forever under `settings`, cleared on save).
- Seed copy lives in `database/seeders/data/*.php` — edit text there, not in seeder classes.
  Seeders are idempotent (`php artisan db:seed` is safe to re-run). Settings seed with `firstOrCreate` so real values are never overwritten.
- Tests: `php artisan test` (in-memory SQLite, does not touch MySQL).

## Admin panel (Filament)

- Navigation groups (in order) live in `App\Filament\Support\NavGroup`; resources return `__(NavGroup::X)`.
- Build translatable inputs with `App\Filament\Support\Fields` (`text`/`textarea`/`rich` add `dir="auto"`;
  `image`/`vectorOrImage`/`video` apply upload limits). Shared table bits: `App\Filament\Support\TableKit`.
- Translatable resources: `use Translatable` on the resource + page concerns + `LocaleSwitcher` header action;
  relation managers use the RM `Translatable` concern + `Tables\Actions\LocaleSwitcher`.
- Admin UI language: `users.admin_locale` applied by `SetAdminLocale` (must run BEFORE `DispatchServingFilamentEvent`,
  which builds the navigation). Admin labels are English and translated via `lang/ar.json` — add every new label there.
- Permissions: Shield. **Re-generate with `php artisan shield:generate --all --panel=admin --ignore-existing-policies`**
  — `UserPolicy` and `UiStringPolicy` contain hand-written rules; `ServicePolicy`/`PageBlockPolicy` are hand-made.
  The role → permission matrix is `ShieldPermissionSeeder` (idempotent, part of `DatabaseSeeder`).
- User protection: non-super admins never see/edit super admins or grant `super_admin` (validated server-side);
  nobody can delete/deactivate themselves (also enforced in `Gate::before` for super admins).
- Table reorder (`->reorderable('sort')`) writes with a query update — no model events fire (no activity log).

## Local notes

- Run all commands from inside `pixellap-api/`.
- In `APP_ENV=local`, GET API requests without an `X-Site-Key` header are allowed (browser testing). A header that is sent must be valid.
- `php artisan db:show` fails on XAMPP MariaDB (`performance_schema.session_status` missing) — harmless; use `php artisan migrate:status` instead.
