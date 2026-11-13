<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Accepted mime types for media library collections.
 */
final class MimeTypes
{
    public const IMAGES = ['image/jpeg', 'image/png', 'image/webp'];

    public const SVG = ['image/svg+xml'];

    public const LOGOS = ['image/svg+xml', 'image/png', 'image/webp'];

    public const ICONS = ['image/svg+xml', 'image/png', 'image/webp'];

    public const VIDEOS = ['video/mp4', 'video/webm'];
}
