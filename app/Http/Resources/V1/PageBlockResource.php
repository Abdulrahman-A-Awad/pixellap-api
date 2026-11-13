<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Enums\PageBlockType;
use App\Models\PageBlock;
use Illuminate\Http\Request;

/**
 * Discriminated by `type`:
 *  text, cta       → title, body
 *  image_text      → title, body, image
 *  mission_vision,
 *  values          → title, items [{title, text}]
 * `body` / item `text` are plain text (paragraphs separated by a blank line).
 *
 * @property PageBlock $resource
 */
class PageBlockResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $block = $this->resource;
        $base = [
            'id' => $block->id,
            'type' => $block->type->value,
            'title' => self::text($block->title),
        ];

        return match ($block->type) {
            PageBlockType::Text, PageBlockType::Cta => [...$base, 'body' => self::text($block->body)],
            PageBlockType::ImageText => [
                ...$base,
                'body' => self::text($block->body),
                'image' => MediaResource::fromModel($block, 'image'),
            ],
            PageBlockType::MissionVision, PageBlockType::Values => [
                ...$base,
                'items' => array_map(
                    fn (mixed $item): array => [
                        'title' => (string) (is_array($item) ? ($item['title'] ?? '') : ''),
                        'text' => (string) (is_array($item) ? ($item['text'] ?? '') : ''),
                    ],
                    self::list($block->items),
                ),
            ],
        };
    }
}
