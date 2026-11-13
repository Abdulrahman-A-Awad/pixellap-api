<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Service;
use Illuminate\Http\Request;

/** @property Service $resource */
class ServiceResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $service = $this->resource;

        return [
            'id' => $service->id,
            'title' => (string) $service->title,
            'description' => (string) $service->description, // plain text
            'features' => array_values(array_filter(self::list($service->features), 'is_string')),
            'icon' => MediaResource::fromModel($service, 'icon'),
            'image' => MediaResource::fromModel($service, 'image'),
        ];
    }
}
