<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;

/**
 * Category card (GET /service-categories). Expects `services_count` from withCount().
 *
 * @property ServiceCategory $resource
 */
class ServiceCategoryResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $category = $this->resource;

        return [
            'slug' => $category->slug,
            'title' => (string) $category->title,
            'excerpt' => (string) $category->excerpt,
            'icon' => MediaResource::fromModel($category, 'icon'),
            'cover' => MediaResource::fromModel($category, 'cover'),
            'services_count' => (int) ($category->services_count ?? 0),
        ];
    }
}
