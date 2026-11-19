<?php

declare(strict_types=1);

use App\Models\HomeSection;
use Illuminate\Database\Migrations\Migration;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The hero's single `image` (poster) + `video` collections were replaced by the loop video
 * variants (HomeSection::HERO_MEDIA). Deletes the old placeholder files (rows + files on disk).
 * Data only — the media table is unchanged, so there is nothing to roll back.
 */
return new class extends Migration
{
    public function up(): void
    {
        $morph = (new HomeSection)->getMorphClass();
        $heroIds = HomeSection::query()->where('key', 'hero')->pluck('id');

        Media::query()
            ->where('model_type', $morph)
            ->where(fn ($q) => $q
                ->where('collection_name', 'video') // no longer registered on any section
                ->orWhere(fn ($q) => $q->whereIn('model_id', $heroIds)->where('collection_name', 'image')))
            ->get()
            ->each(fn (Media $media) => $media->delete());
    }

    public function down(): void
    {
        // Deleted files cannot be restored.
    }
};
