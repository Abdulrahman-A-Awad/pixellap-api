<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\HomeSectionKey;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\RevalidationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;
use Throwable;

/**
 * Bulk-attaches content images from a JSON manifest. Each image is resized to at most
 * 2560 px on the long edge, converted to WebP q82 and added to the model's collection
 * (replacing what is there). The source files are never modified.
 *
 * Manifest: a JSON array of entries (`file` is relative to the manifest's folder):
 *   {"model": "service_category", "slug": "digital-marketing",            "collection": "cover", "file": "…"}
 *   {"model": "service",          "slug": "<category slug>", "title": "<English service title>",
 *                                                                          "collection": "image", "file": "…"}
 *   {"model": "page",             "key": "about",                         "collection": "hero",  "file": "…"}
 *   {"model": "page_block",       "key": "about", "type": "image_text",   "collection": "image", "file": "…"}
 *   {"model": "home_section",     "key": "about",                         "collection": "image", "file": "…"}
 *
 * Idempotent: the source file's hash is stored on the media; an unchanged entry is skipped
 * (use --force to re-import). The website is revalidated once at the end.
 */
class ImportMedia extends Command
{
    public const MAX_EDGE = 2560;

    public const QUALITY = 82;

    protected $signature = 'pixellap:import-media {manifest : Path to the manifest JSON} {--force : Re-import entries whose source has not changed}';

    protected $description = 'Import content images (resized WebP) from a JSON manifest';

    public function handle(RevalidationService $revalidation): int
    {
        $manifest = (string) $this->argument('manifest');
        $entries = is_file($manifest) ? json_decode((string) file_get_contents($manifest), true) : null;
        if (! is_array($entries) || ! array_is_list($entries)) {
            $this->components->error("Cannot read a JSON array from {$manifest}");

            return self::FAILURE;
        }

        $base = dirname((string) realpath($manifest));
        $tmp = storage_path('app/import-media');
        if (! is_dir($tmp)) {
            mkdir($tmp, 0775, true);
        }

        $counts = ['imported' => 0, 'unchanged' => 0, 'failed' => 0];
        foreach ($entries as $i => $entry) {
            $label = sprintf('#%d %s', $i + 1, $this->describe((array) $entry));
            try {
                $result = $this->import((array) $entry, $base, $tmp);
                $counts[$result]++;
                $this->components->twoColumnDetail($label, $result);
            } catch (Throwable $e) {
                $counts['failed']++;
                $this->components->twoColumnDetail($label, '<fg=red>failed</>');
                $this->components->error($e->getMessage());
            }
        }

        @rmdir($tmp);
        $revalidation->flush();
        $this->components->info(sprintf('Imported %d, unchanged %d, failed %d.', $counts['imported'], $counts['unchanged'], $counts['failed']));

        return $counts['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return 'imported'|'unchanged'
     */
    private function import(array $entry, string $base, string $tmp): string
    {
        $collection = (string) ($entry['collection'] ?? '');
        $source = $base.DIRECTORY_SEPARATOR.ltrim((string) ($entry['file'] ?? ''), '/\\');
        if (! is_file($source)) {
            throw new RuntimeException("File not found: {$source}");
        }

        $model = $this->resolve($entry);
        if ($model->getMediaCollection($collection) === null) {
            throw new RuntimeException(sprintf('%s has no "%s" collection.', class_basename($model), $collection));
        }

        $hash = sha1_file($source).':'.self::MAX_EDGE.':'.self::QUALITY;
        $current = $model->getFirstMedia($collection);
        if (! $this->option('force') && $current?->getCustomProperty('import_source') === $hash) {
            return 'unchanged';
        }

        $name = pathinfo((string) ($entry['name'] ?? $collection.'-'.$model->getKey()), PATHINFO_FILENAME);
        $out = $tmp.DIRECTORY_SEPARATOR.$name.'.webp';
        Image::load($source)
            ->fit(Fit::Max, self::MAX_EDGE, self::MAX_EDGE)
            ->quality(self::QUALITY)
            ->save($out);

        // Moves the temp file into the media library; singleFile() collections drop the old file.
        $model->addMedia($out)
            ->usingFileName($name.'.webp')
            ->withCustomProperties(['import_source' => $hash])
            ->toMediaCollection($collection);

        return 'imported';
    }

    /** @param array<string, mixed> $entry */
    private function resolve(array $entry): Model&HasMedia
    {
        $key = (string) ($entry['key'] ?? '');
        $slug = (string) ($entry['slug'] ?? '');

        $model = match ($entry['model'] ?? null) {
            'home_section' => HomeSection::query()->where('key', HomeSectionKey::from($key))->first(),
            'page' => Page::query()->where('key', $key)->first(),
            'page_block' => PageBlock::query()
                ->whereHas('page', fn ($q) => $q->where('key', $key))
                ->where('type', (string) ($entry['type'] ?? ''))
                ->orderBy('sort')
                ->first(),
            'service_category' => ServiceCategory::query()->where('slug', $slug)->first(),
            'service' => Service::query()
                ->whereHas('serviceCategory', fn ($q) => $q->where('slug', $slug))
                ->where('title->en', (string) ($entry['title'] ?? ''))
                ->first(),
            default => throw new RuntimeException('Unknown model: '.json_encode($entry['model'] ?? null)),
        };

        if (! $model instanceof Model || ! $model instanceof HasMedia) {
            throw new RuntimeException('No matching record for '.$this->describe($entry));
        }

        return $model;
    }

    /** @param array<string, mixed> $entry */
    private function describe(array $entry): string
    {
        $id = array_filter([$entry['key'] ?? null, $entry['slug'] ?? null, $entry['type'] ?? null, $entry['title'] ?? null]);

        return sprintf('%s[%s] → %s', $entry['model'] ?? '?', implode(' / ', $id), $entry['collection'] ?? '?');
    }
}
