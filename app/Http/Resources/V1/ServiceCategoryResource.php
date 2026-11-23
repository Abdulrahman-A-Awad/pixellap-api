<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\ServiceAnchors;
use Illuminate\Http\Request;

/**
 * Category card (GET /service-categories). Expects `services_count` from withCount().
 * With `?include=services` (services index page) it also carries the rich `description`, the
 * `hero_video` and the visible services as light rows: {id, title, short, anchor} — `anchor` is the
 * same id the category page gives the service card (App\Support\ServiceAnchors).
 *
 * @property ServiceCategory $resource
 */
class ServiceCategoryResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $category = $this->resource;

        $data = [
            'slug' => $category->slug,
            'title' => (string) $category->title,
            'excerpt' => (string) $category->excerpt,
            'icon' => MediaResource::fromModel($category, 'icon'),
            'cover' => MediaResource::fromModel($category, 'cover'),
            'services_count' => (int) ($category->services_count ?? 0),
        ];

        if (self::includesServices($request)) {
            $anchors = ServiceAnchors::for($category->services);
            $data += [
                'description' => (string) $category->description, // HTML (rich editor)
                'hero_video' => MediaResource::fromModel($category, 'hero_video'),
                'services' => $category->services->map(fn (Service $service): array => [
                    'id' => $service->id,
                    'title' => (string) $service->title,
                    'short' => ServiceAnchors::short($service->description),
                    'anchor' => $anchors[$service->id],
                ])->values()->all(),
            ];
        }

        return $data;
    }

    /** `?include=services` (comma-separated includes; unknown values are ignored). */
    public static function includesServices(Request $request): bool
    {
        return in_array('services', array_map('trim', explode(',', (string) $request->query('include', ''))), true);
    }
}
