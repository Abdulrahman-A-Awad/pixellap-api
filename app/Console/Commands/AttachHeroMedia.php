<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\HomeSectionKey;
use App\Filament\Resources\HomeSectionResource;
use App\Models\HomeSection;
use App\Services\RevalidationService;
use Illuminate\Console\Command;

/**
 * Attaches the hero loop video files from a folder (copies them, the folder is left untouched):
 *
 *   hero-wide.webm      hero-wide.mp4      hero-wide-poster.webp
 *   hero-vertical.webm  hero-vertical.mp4  hero-vertical-poster.webp
 *
 * Each found file replaces the current one in its collection; missing files are skipped.
 * Posters may also be .jpg / .png. Revalidates the website once at the end.
 */
class AttachHeroMedia extends Command
{
    protected $signature = 'pixellap:attach-hero-media {dir : Folder with hero-wide.webm, hero-wide.mp4, hero-wide-poster.webp and the same for vertical}';

    protected $description = 'Attach the hero loop videos + posters from a folder';

    public function handle(RevalidationService $revalidation): int
    {
        $dir = rtrim((string) $this->argument('dir'), '/\\');
        if (! is_dir($dir)) {
            $this->components->error("Not a folder: {$dir}");

            return self::FAILURE;
        }

        $hero = HomeSection::query()->where('key', HomeSectionKey::Hero)->first();
        if ($hero === null) {
            $this->components->error('The hero section does not exist (run the seeders first).');

            return self::FAILURE;
        }

        $attached = 0;
        foreach (HomeSection::HERO_MEDIA as $variant => $collections) {
            foreach ($collections as $format => $collection) {
                $file = $this->find($dir, $variant, $format);
                if ($file === null) {
                    $this->components->warn("{$variant} {$format}: not found, skipped.");

                    continue;
                }

                $limitKb = $format === 'poster' ? HomeSectionResource::HERO_POSTER_MAX_KB : HomeSectionResource::HERO_VIDEO_MAX_KB;
                $sizeKb = (int) ceil(filesize($file) / 1024);
                if ($sizeKb > $limitKb) {
                    $this->components->error(sprintf('%s: %d KB is over the %d KB admin limit, skipped.', basename($file), $sizeKb, $limitKb));

                    continue;
                }

                $hero->addMedia($file)->preservingOriginal()->toMediaCollection($collection);
                $this->components->twoColumnDetail(basename($file), "{$collection} ({$sizeKb} KB)");
                $attached++;
            }
        }

        $revalidation->flush();
        $this->components->info("{$attached} file(s) attached to the hero.");

        return $attached > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function find(string $dir, string $variant, string $format): ?string
    {
        $candidates = $format === 'poster'
            ? array_map(fn (string $ext): string => "hero-{$variant}-poster.{$ext}", ['webp', 'jpg', 'jpeg', 'png'])
            : ["hero-{$variant}.{$format}"];

        foreach ($candidates as $name) {
            $path = $dir.DIRECTORY_SEPARATOR.$name;
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
