<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ServiceCategory;
use App\Services\PublicContent;
use Illuminate\Http\Request;

/**
 * GET /site — global data for the layout (header, footer, SEO defaults, UI strings).
 *
 * @property PublicContent $resource
 */
class SiteResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $content = $this->resource;

        return [
            'settings' => $content->settings(),
            'ui_strings' => (object) $content->uiStrings(),
            'nav' => [
                'categories' => $content->categories()->map(fn (ServiceCategory $c): array => [
                    'slug' => $c->slug,
                    'title' => (string) $c->title,
                    'sort' => $c->sort,
                ])->all(),
            ],
            'locales' => array_values((array) config('app.available_locales')),
        ];
    }
}
