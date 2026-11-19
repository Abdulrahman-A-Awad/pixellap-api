<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Providers\AppServiceProvider;
use App\Services\RevalidationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * One-off, idempotent copy fix: the brand font RH-Zak has no "·" (U+00B7) glyph.
 * Replaces every middle dot (and the spaces around it) with " | " in all translatable
 * content fields and setting values. Saves through the models, so activity is logged and
 * the website is revalidated once at the end. Safe to re-run (nothing left to replace).
 */
class ReplaceMiddleDots extends Command
{
    public const DOT = "\u{00B7}";

    public const REPLACEMENT = ' | ';

    protected $signature = 'pixellap:replace-middle-dots {--dry-run : List the changes without saving}';

    protected $description = 'Replace "·" (missing from the brand font) with " | " in all stored content';

    public function handle(RevalidationService $revalidation): int
    {
        $dry = (bool) $this->option('dry-run');
        $changed = 0;

        foreach (AppServiceProvider::CONTENT_MODELS as $class) {
            /** @var class-string<Model> $class */
            $class::query()->orderBy('id')->each(function (Model $model) use ($dry, &$changed): void {
                $fields = $this->fix($model);
                if ($fields === []) {
                    return;
                }

                $changed++;
                $this->line(sprintf('%s #%s: %s', class_basename($model), $model->getKey(), implode(', ', $fields)));
                if (! $dry) {
                    $model->save();
                }
            });
        }

        if (! $dry) {
            $revalidation->flush();
        }

        $this->components->info($changed === 0
            ? 'No "·" found.'
            : ($dry ? "{$changed} record(s) would change." : "{$changed} record(s) updated."));

        return self::SUCCESS;
    }

    /**
     * Fix the model in memory; returns the names of the changed fields.
     *
     * @return list<string>
     */
    private function fix(Model $model): array
    {
        $changed = [];

        /** @var list<string> $translatable */
        $translatable = property_exists($model, 'translatable') ? $model->translatable : [];
        foreach ($translatable as $field) {
            $before = $model->getTranslations($field);
            $after = self::replaceDeep($before);
            if ($after !== $before) {
                $model->setTranslations($field, $after);
                $changed[] = $field;
            }
        }

        // Settings keep any type of value as JSON (text, {en, ar}, objects).
        if ($translatable === [] && array_key_exists('value', $model->getAttributes())) {
            $before = $model->getAttribute('value');
            $after = self::replaceDeep($before);
            if ($after !== $before) {
                $model->setAttribute('value', $after);
                $changed[] = 'value';
            }
        }

        return $changed;
    }

    public static function replace(string $text): string
    {
        return str_contains($text, self::DOT)
            ? (string) preg_replace('/[ \t]*\x{00B7}[ \t]*/u', self::REPLACEMENT, $text)
            : $text;
    }

    private static function replaceDeep(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::replace($value);
        }
        if (is_array($value)) {
            return array_map(self::replaceDeep(...), $value);
        }

        return $value;
    }
}
