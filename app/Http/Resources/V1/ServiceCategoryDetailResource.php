<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;

/**
 * GET /service-categories/{slug}. Expects visible `services` and `faqs` eager loaded.
 *
 * @property ServiceCategory $resource
 */
class ServiceCategoryDetailResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $category = $this->resource;

        return [
            'slug' => $category->slug,
            'title' => (string) $category->title,
            'excerpt' => (string) $category->excerpt,
            'description' => (string) $category->description, // HTML (rich editor)
            'seo' => [
                'title' => self::text($category->seo_title),
                'description' => self::text($category->seo_description),
            ],
            'icon' => MediaResource::fromModel($category, 'icon'),
            'cover' => MediaResource::fromModel($category, 'cover'),
            'hero_video' => MediaResource::fromModel($category, 'hero_video'),
            'services' => ServiceResource::collection($category->services),
            'faqs' => FaqResource::collection($category->faqs),
            'updated_at' => $category->updated_at?->toIso8601String(),
        ];
    }
}
