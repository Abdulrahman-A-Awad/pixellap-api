<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Faq;
use Illuminate\Http\Request;

/** @property Faq $resource */
class FaqResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'question' => (string) $this->resource->question,
            'answer' => (string) $this->resource->answer, // HTML (rich editor)
        ];
    }
}
