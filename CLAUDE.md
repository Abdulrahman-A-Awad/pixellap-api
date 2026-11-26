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
- New UI strings on existing installs: add them to `data/ui_strings.php` AND a small data migration that `firstOrCreate`s
  just those keys (e.g. `2026_11_21_100000_add_missing_ui_strings_refine_home`) — deploys run migrations; `UiStringSeeder`
  uses `updateOrCreate` and would overwrite texts editors changed in the admin.
- Rewriting seeded copy on existing installs: update `data/*.php` AND a data migration that replaces a value only while it
  still equals the ORIGINAL seed text, matched by text not position (e.g. `2026_11_24_100000_faq_answers_readable`,
  reversible) — editor changes are never overwritten.
- `business_hours` setting (json `{days: ['mon'…], open: 'HH:MM', close: 'HH:MM', timezone}`): edited in Site settings →
  Contact (day checkboxes + time pickers, close must be after open); `/site` returns it normalised by
  `PublicContent::businessHours()` (null when unusable). The website's "Open now / Closed" chip uses it; `working_hours`
  stays the display text.
- Tests: `php artisan test` (in-memory SQLite, does not touch MySQL).

## Admin panel (Filament)

- Navigation: Dashboard + Messages ungrouped, then `App\Filament\Support\NavGroup` CONTENT (Home Page, Services, Pages),
  SETTINGS (Site settings, Interface texts), ACCESS (Users + Activity log for admin and super_admin, Roles for
  super_admin only — driven purely by the policies/`canViewAny`; the group shows when any item is visible). Groups are not collapsible.
- Stats, process steps, clients and home FAQs have NO resource: they are edited inside their home section
  (`EditHomeSection` footer → `App\Filament\Widgets\HomeItems\*Table`: modals, drag reorder via `RevalidatesOnReorder`,
  logged on the section). Their permissions are created by `ShieldPermissionSeeder::CUSTOM`.
- Dashboard (greeting + date header, View website / Refresh cache actions), widgets decide their own visibility:
  `StatsOverview` (4 KPIs, needs Messages → View; services card needs Services → View), `QuickActions` (tiles filtered by
  permission; hidden when empty), `WebsiteReadiness` (compact bar; admins + whoever edits home/settings; checks in
  `App\Support\WebsiteReadiness`, cached 60 s, cleared by `RevalidationService::touch()` and User saves), `LatestMessages`
  (Messages → View), `MessagesChart` (30-day bars, super_admin + admin). No `widget_*` permissions (Shield widgets disabled).
- Branding (logo, favicon, RH-Zak via `public/fonts/rh-zak.css`, login tagline hook in `resources/views/filament/auth`) is set
  in `AdminPanelProvider`. **Theme: `public/css/pixellap-admin.css`** (plain CSS, `FilamentAsset` Css::make, versioned by
  filemtime). Filament only ships the Tailwind classes it uses: style new custom views with `px-*` classes in that file
  (logical properties for RTL), not arbitrary Tailwind utilities.
- Topbar: global search (messages, service categories incl. service titles, pages), View website icon, bell, user menu
  (name + role header, My profile (`->profile()`), theme, language, sign out).
- Every form field: plain-language label + helper text, both in `lang/ar.json`. Home section fields per key come from
  `HomeSectionKey::fields()` — the form and the API hide what the website does not render.
- Build translatable inputs with `App\Filament\Support\Fields` (`text`/`textarea`/`rich` add `dir="auto"`;
  `image`/`vectorOrImage`/`video` apply upload limits). Shared table bits: `App\Filament\Support\TableKit`.
- **NO language switcher anywhere in the admin** (Home Page, Site settings, Services + their services/FAQs, Pages + blocks;
  no `Translatable` concerns, no `LocaleSwitcher`, no SpatieLaravelTranslatablePlugin). Every translatable field is
  `Fields::bilingual($name, $label, 'text'|'textarea'|'rich', $help, required:)` (EN | AR side by side, `{name}.en/.ar`;
  EN required when the field is; empty AR = warning hint only), tables use `TableKit::bilingualColumns()` ("AR missing"
  badge). Each section form starts with `HomeSectionKey::where()`; sections that need items show
  `emptyNotice()` + a "Hidden: no items" list badge (`HomeSectionResource::isHiddenForNoItems`). The hero has no title
  (H1 = tagline setting). Hero video uploads are `previewable(false)->openable()` (no download on page load).
  Lists per language: `Fields::bilingual(..., 'tags')` (service features) and two repeaters `items.en` | `items.ar` (page
  blocks; only English enforces the card count). `tap:` customises each input (max length, slug from `title.en`).
- Global search is Arabic-aware: resources override `applyGlobalSearchAttributeConstraints()` with
  `App\Support\ArabicSearch::apply()` (locale JSON paths, diacritics / أإآ normalised on both sides, LIKE wildcards escaped).
  Add new searchable resources the same way.
- `site_name` is a translatable setting ("Pixellap" / "بكسل لاب"). Brand icons for the admin: `resources/svg/brands`
  (blade-icons set `brand-*`, registered in `AppServiceProvider::register`).
- Admin UI language: ONE switch, the user-menu item → `POST /admin/locale/{en|ar}` (`SwitchAdminLocaleController`) writes
  `users.admin_locale` (default `ar` for new accounts; factories/the local dev admin use `en` because tests assert English)
  + an `admin_locale` cookie for the login page. `SetAdminLocale` (must run BEFORE `DispatchServingFilamentEvent`, which
  builds the navigation) reads them, falling back to `ar`. Never add another language field/toggle. Admin labels are
  English and translated via `lang/ar.json` — add every new label there.
- Permissions: Shield. **Re-generate with `php artisan shield:generate --all --panel=admin --ignore-existing-policies`**
  — `UserPolicy` and `UiStringPolicy` contain hand-written rules; `ServicePolicy`/`PageBlockPolicy` are hand-made.
  The seeded role → permission sets are `ShieldPermissionSeeder` (idempotent, part of `DatabaseSeeder`).
- Roles screen: our own `App\Filament\Resources\RoleResource` (`/admin/roles`; Shield registers its resource only when no
  `\RoleResource` exists). One matrix of areas × View/Add/Edit/Delete/Reorder from `App\Filament\Support\PermissionMatrix`
  (single source: area → Shield identifiers; never writes replicate/restore/force_delete/widget permissions; Site settings =
  `page_ManageSettings`). New resource/area → add it to `PermissionMatrix::areas()` and `ActivityDescriber::subjects()`.
  `super_admin` ("Full access") can never be edited/deleted (resource + `Gate::before`); built-in roles keep their name and
  cannot be deleted (`PermissionMatrix::BUILT_IN`). `roles.description` = the one-liner in the list and the user form.
- Activity log: sentences via `App\Support\ActivityDescriber` ("{causer} edited the Home page section: Hero"), filters
  area/user/event/date, bulk delete (policy `deleteAny`), "Clear entire log" (super_admin, typed CONFIRM; truncates and logs
  `cleared`). Retention 30 days: `activitylog.delete_records_older_than_days` + daily `activitylog:clean --days=30` (03:15).
- User protection: non-super admins never see/edit super admins or grant `super_admin` (validated server-side);
  nobody can delete/deactivate themselves (also enforced in `Gate::before` for super admins).
- Table reorder (`->reorderable('sort')`) writes with a query update — no model events fire (no activity log).

## Public API & revalidation

- Contract for the frontend: `docs/API.md` + `docs/api-types.ts`. **Update both whenever a V1 resource changes.**
- Routes `routes/api.php` → `Api\V1\ContentController` (GETs) / `ContactController`. Queries live in
  `App\Services\PublicContent`; JSON shape in `app/Http/Resources/V1` (`MediaResource` for every file).
- `App\Support\ApiResponder` wraps `{data, meta}` and caches the encoded JSON 1 h per endpoint+params+locale
  through `App\Support\ApiCache` (tag registry under `api_tags:{tag}` — works on file/database cache).
- Service anchors: `App\Support\ServiceAnchors` (slugified English title, unique per category, `short` = first
  sentence ≤ 140 chars) feeds BOTH `GET /service-categories?include=services` and the category detail's `services[].anchor`
  — the website deep-links `/services/{slug}#{anchor}`, so never compute anchors anywhere else.
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

- Hosts (server 187.127.187.118): website `https://ppixelmedia.com` (www → apex), API + admin `https://api.ppixelmedia.com`.
  The domain is ppixelmedia.com; the brand name stays "Pixellap". Nginx: `deploy/nginx/{ppixelmedia.com,api.ppixelmedia.com}.conf`.
- PRE-LAUNCH (until go-live): `/etc/nginx/pixellap-prelaunch/` (from `deploy/nginx/prelaunch/`) adds basic auth (user
  `preview`) on the website and X-Robots-Tag on both sites via glob includes; go-live = delete it (runbook §13).
  Everyday deploys from a PC: `ssh deploy@187.127.187.118 "~/deploy-web.sh"` / `"~/deploy-api.sh"`.
  deploy has NOPASSWD sudo only for the commands in `/etc/sudoers.d/91-deploy-limited`; everything else asks for a password.
- Deploy kit + runbook: `deploy/README.md` (one Ubuntu 24.04 box: Nginx, PHP 8.3-FPM pool `pixellap` running as
  `deploy`, MySQL 8, PM2 for the website, queue worker = `pixellap-queue` systemd service, cron for the scheduler + backups).
  Env template: `.env.production.example`. Scripts in `deploy/scripts` must stay `shellcheck`-clean.
- Commands: `pixellap:check-production` (fails on debug/secrets/default admin/TODO settings/storage link/queue tables/
  SMTP placeholders + invalid from/notify addresses),
  `pixellap:create-super-admin` (production has NO seeded admin — `RoleSeeder` skips it when `APP_ENV=production`),
  `pixellap:revalidate-all` (sync full refresh; deploy scripts call it).
- Media commands (revalidate once at the end): `pixellap:attach-hero-media {dir}` (hero-{wide,vertical}.{webm,mp4} +
  -poster.webp → the hero's `HomeSection::HERO_MEDIA` collections; the clean production cuts live in
`deploy/hero-media`), `pixellap:import-media {manifest.json}` (resize to
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
