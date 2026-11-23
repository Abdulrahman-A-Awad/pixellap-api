<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Service;
use Illuminate\Http\Request;

/**
 * One service on the category page. `anchor` (from App\Support\ServiceAnchors, computed for the whole
 * category) is the card's in-page id: /services/{slug}#{anchor}.
 *
 * @property Service $resource
 */
class ServiceResource extends Resource
{
    private string $anchor = '';

    public function withAnchor(string $anchor): static
    {
        $this->anchor = $anchor;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $service = $this->resource;

        return [
            'id' => $service->id,
            'anchor' => $this->anchor !== '' ? $this->anchor : 'service-'.$service->id,
            'title' => (string) $service->title,
            'description' => (string) $service->description, // plain text
            'features' => array_values(array_filter(self::list($service->features), 'is_string')),
            'icon' => MediaResource::fromModel($service, 'icon'),
            'image' => MediaResource::fromModel($service, 'image'),
        ];
    }
}
