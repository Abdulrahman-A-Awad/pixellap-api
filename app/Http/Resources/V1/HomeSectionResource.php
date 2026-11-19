<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Enums\HomeSectionKey;
use App\Models\Client;
use App\Models\HomeSection;
use App\Models\ProcessStep;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Stat;
use App\Services\PublicContent;
use Illuminate\Http\Request;

/**
 * One home section, discriminated by `key`. Common fields + section-specific data:
 *  stats       → items      [{value, prefix, suffix, label}]
 *  services    → categories [{slug, title, excerpt, icon, cover, services: [title]}]
 *  process     → steps      [{title, description, icon}]
 *  clients     → items      [{name, url, logo}]
 *  faq         → faqs       [{id, question, answer}]
 *  contact_cta → response_time, whatsapp
 *  hero        → media      {wide, vertical: {webm, mp4, poster}} (absolute URLs or null)
 * Fields a section does not use (see HomeSectionKey::fields()) are always null.
 *
 * @property HomeSection $resource
 */
class HomeSectionResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $section = $this->resource;
        $key = $section->key;
        $content = app(PublicContent::class);

        $base = [
            'key' => $key->value,
            'eyebrow' => self::text($section->eyebrow),
            'title' => self::text($section->title),
            'subtitle' => self::text($section->subtitle),
            'cta' => $key->supports('cta') ? self::link($section->cta_label, $section->cta_url) : null,
            'cta2' => $key->supports('cta2') ? self::link($section->cta2_label, $section->cta2_url) : null,
            'image' => $key->supports('image') ? MediaResource::fromModel($section, 'image') : null,
        ];

        return [...$base, ...match ($key) {
            HomeSectionKey::Stats => [
                'items' => $content->stats()->map(fn (Stat $s): array => [
                    'value' => $s->value,
                    'prefix' => self::text($s->prefix),
                    'suffix' => self::text($s->suffix),
                    'label' => (string) $s->label,
                ])->all(),
            ],
            HomeSectionKey::Services => [
                'categories' => $content->categories()->map(fn (ServiceCategory $c): array => [
                    'slug' => $c->slug,
                    'title' => (string) $c->title,
                    'excerpt' => (string) $c->excerpt,
                    'icon' => MediaResource::fromModel($c, 'icon'),
                    'cover' => MediaResource::fromModel($c, 'cover'),
                    'services' => $c->services->map(fn (Service $s): string => (string) $s->title)->all(),
                ])->all(),
            ],
            HomeSectionKey::Process => [
                'steps' => $content->processSteps()->map(fn (ProcessStep $p): array => [
                    'title' => (string) $p->title,
                    'description' => (string) $p->description,
                    'icon' => self::text($p->icon),
                ])->all(),
            ],
            HomeSectionKey::Clients => [
                'items' => $content->clients()->map(fn (Client $c): array => [
                    'name' => $c->name,
                    'url' => self::text($c->url),
                    'logo' => MediaResource::fromModel($c, 'logo'),
                ])->all(),
            ],
            HomeSectionKey::Faq => [
                'faqs' => FaqResource::collection($content->homeFaqs()),
            ],
            HomeSectionKey::ContactCta => [
                'response_time' => self::text(Setting::get('response_time')),
                'whatsapp' => self::text(Setting::get('whatsapp')),
            ],
            HomeSectionKey::Hero => [
                'media' => array_map(
                    fn (array $variant): array => array_map(
                        fn (string $collection): ?string => $section->getFirstMedia($collection)?->getFullUrl(),
                        $variant,
                    ),
                    HomeSection::HERO_MEDIA,
                ),
            ],
            HomeSectionKey::About => [],
        }];
    }
}
