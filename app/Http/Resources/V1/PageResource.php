<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Page;
use Illuminate\Http\Request;

/**
 * GET /pages/{key}. Expects visible `blocks` eager loaded.
 *
 * @property Page $resource
 */
class PageResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $page = $this->resource;

        return [
            'key' => $page->key->value,
            'title' => (string) $page->title,
            'subtitle' => self::text($page->subtitle),
            'seo' => [
                'title' => self::text($page->seo_title),
                'description' => self::text($page->seo_description),
            ],
            'hero' => MediaResource::fromModel($page, 'hero'),
            'blocks' => PageBlockResource::collection($page->blocks),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }
}
