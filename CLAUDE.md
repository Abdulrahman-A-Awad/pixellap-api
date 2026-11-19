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
- **Cache revalidation:** content changes revalidate the API cache and the Next.js site (see "Public API & revalidation").

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

- Navigation: Dashboard + Messages ungrouped, then `App\Filament\Support\NavGroup` CONTENT (Home Page, Services, Pages),
  SETTINGS (Site settings, Interface texts), ACCESS (Users, Roles, Activity log — in the menu for super admins only;
  the policies are unchanged). Groups are not collapsible.
- Stats, process steps, clients and home FAQs have NO resource: they are edited inside their home section
  (`EditHomeSection` footer → `App\Filament\Widgets\HomeItems\*Table`: modals, drag reorder via `RevalidatesOnReorder`,
  logged on the section). Their permissions are created by `ShieldPermissionSeeder::CUSTOM`.
- Dashboard: `StatsOverview`, `WebsiteReadiness` (admins; checks in `App\Support\WebsiteReadiness`, cached 60 s, cleared by
  `RevalidationService::touch()` and User saves), `LatestMessages`. Branding (logo, favicon, RH-Zak via
  `public/fonts/rh-zak.css`, login hook views in `resources/views/filament/auth`) is set in `AdminPanelProvider`.
- Every form field: plain-language label + helper text, both in `lang/ar.json`. Home section fields per key come from
  `HomeSectionKey::fields()` — the form and the API hide what the website does not render.
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

## Public API & revalidation

- Contract for the frontend: `docs/API.md` + `docs/api-types.ts`. **Update both whenever a V1 resource changes.**
- Routes `routes/api.php` → `Api\V1\ContentController` (GETs) / `ContactController`. Queries live in
  `App\Services\PublicContent`; JSON shape in `app/Http/Resources/V1` (`MediaResource` for every file).
- `App\Support\ApiResponder` wraps `{data, meta}` and caches the encoded JSON 1 h per endpoint+params+locale
  through `App\Support\ApiCache` (tag registry under `api_tags:{tag}` — works on file/database cache).
- GETs use `throttle:120,1,api` (named counter). Contact uses the `contact` RateLimiter (AppServiceProvider):
  5/min keyed by `App\Support\ClientIp` — `X-Client-IP` only with a valid `X-Site-Key`, else `$request->ip()`;
  the same IP is stored on the lead. Never trust `X-Client-IP` anywhere else.
- **Tag map:** `App\Support\CacheTags` is the single source of truth (local cache AND webhook).
  New content model → add it to `AppServiceProvider::CONTENT_MODELS` and to `CacheTags::for()`.
- `ContentObserver` / `MediaObserver` / `HandleMediaAdded` call `RevalidationService::touch()`; tags are collected
  per request and flushed once on `app()->terminating()` → local cache flush + ONE queued `RevalidateFrontend` job.
  Code outside a request (tinker, jobs) must call `app(RevalidationService::class)->flush()` itself.
- Every reorderable table uses `App\Filament\Concerns\RevalidatesOnReorder` (revalidates + logs "reordered").
  **Add it to any new `->reorderable()` List page / relation manager.**
- Contact leads: `App\Services\ContactInbox` (store → queued `NewContactMessage` mail → Filament database notification
  to admin + sales). Recipient: `email` setting if valid, else `CONTACT_NOTIFY_EMAIL`.

## Production

- Deploy kit + runbook: `deploy/README.md` (one Ubuntu 24.04 box: Nginx, PHP 8.3-FPM pool `pixellap` running as
  `deploy`, MySQL 8, PM2 for the website, queue worker = `pixellap-queue` systemd service, cron for the scheduler + backups).
  Env template: `.env.production.example`. Scripts in `deploy/scripts` must stay `shellcheck`-clean.
- Commands: `pixellap:check-production` (fails on debug/secrets/default admin/TODO settings/storage link/queue tables),
  `pixellap:create-super-admin` (production has NO seeded admin — `RoleSeeder` skips it when `APP_ENV=production`),
  `pixellap:revalidate-all` (sync full refresh; deploy scripts call it).
- Media commands (revalidate once at the end): `pixellap:attach-hero-media {dir}` (hero-{wide,vertical}.{webm,mp4} +
  -poster.webp → the hero's `HomeSection::HERO_MEDIA` collections), `pixellap:import-media {manifest.json}` (resize to
  2560 px long edge, WebP q82, idempotent via a source hash; see the class doc for the manifest format),
  `pixellap:replace-middle-dots` (one-off: "·" is missing from the brand font → " | ").
- `db:seed` in production overwrites seeded content (`updateOrCreate`): first deploy only (`deploy-api.sh --seed`).
- Security: `SecurityHeaders` on the web group + panel; `RequireHttps` on the panel (production); trusted proxies =
  localhost only; admin login limited 5/min per email+IP (`App\Filament\Pages\Auth\Login`) on top of Filament's per-IP limit.
- OPcache runs with `validate_timestamps=0`: code changes need `sudo systemctl reload php8.3-fpm` (deploy script does it).
- Caches (`optimize`, `filament:optimize`) are built by the deploy script, never committed.

## Local notes

- **Queue:** revalidation webhooks, contact emails and admin notifications are queued (`QUEUE_CONNECTION=database`).
  Run `php artisan queue:work` alongside `php artisan serve`. If the Next.js app is not running, revalidation jobs fail quietly (logged).
- Mail is logged locally (`MAIL_MAILER=log` → `storage/logs/laravel.log`).
- Artisan boots very slowly (~2 min) when XAMPP MySQL is stopped — start MySQL first.

- Run all commands from inside `pixellap-api/`.
- In `APP_ENV=local`, GET API requests without an `X-Site-Key` header are allowed (browser testing). A header that is sent must be valid.
- `php artisan db:show` fails on XAMPP MariaDB (`performance_schema.session_status` missing) — harmless; use `php artisan migrate:status` instead.
