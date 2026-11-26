<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Accent-insensitive search over plain and translatable ({en, ar} JSON) columns, for the admin global search.
 *
 * Why: spatie/laravel-translatable stores JSON with \u-escaped unicode ("ال..."), so a LIKE on the
 * raw column never matches Arabic. We read the locale paths instead (JSON_UNQUOTE(JSON_EXTRACT(col, '$.ar'))
 * on MySQL/MariaDB, json_extract on SQLite) and normalise both sides the same way:
 * lower case, Arabic diacritics + tatweel removed, أ / إ / آ / ٱ → ا.
 */
final class ArabicSearch
{
    /** Tanwin, harakat, shadda, sukun, superscript alef, tatweel. */
    public const DIACRITICS = [
        "\u{064B}", "\u{064C}", "\u{064D}", "\u{064E}", "\u{064F}", "\u{0650}", "\u{0651}", "\u{0652}", "\u{0670}", "\u{0640}",
    ];

    public const ALEF_VARIANTS = ['أ', 'إ', 'آ', 'ٱ'];

    public const LOCALES = ['en', 'ar'];

    public static function normalize(string $text): string
    {
        $text = str_replace(self::DIACRITICS, '', $text);
        $text = str_replace(self::ALEF_VARIANTS, 'ا', $text);

        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($text)));
    }

    /** @return list<string> normalised words of the query */
    public static function terms(string $search): array
    {
        return array_values(array_filter(explode(' ', self::normalize($search)), fn (string $w): bool => $w !== ''));
    }

    /**
     * Adds "(every word matches at least one column)" to the query.
     *
     * @param  list<string>  $translatable  JSON {en, ar} columns of the model
     * @param  list<string>  $plain  ordinary text columns of the model
     * @param  array<string, array{translatable?: list<string>, plain?: list<string>}>  $relations  relation => columns
     */
    public static function apply(Builder $query, string $search, array $translatable = [], array $plain = [], array $relations = []): void
    {
        foreach (self::terms($search) as $term) {
            $query->where(function (Builder $q) use ($term, $translatable, $plain, $relations): void {
                self::orMatch($q, $term, $translatable, $plain);

                foreach ($relations as $relation => $columns) {
                    $q->orWhereHas($relation, fn (Builder $r) => $r->where(
                        fn (Builder $rq) => self::orMatch($rq, $term, $columns['translatable'] ?? [], $columns['plain'] ?? []),
                    ));
                }
            });
        }
    }

    /** Does the text contain every word of the query (both normalised)? Used for result details. */
    public static function matches(?string $value, string $search): bool
    {
        $haystack = self::normalize((string) $value);

        return $haystack !== '' && collect(self::terms($search))->every(fn (string $t): bool => str_contains($haystack, $t));
    }

    /**
     * @param  list<string>  $translatable
     * @param  list<string>  $plain
     */
    private static function orMatch(Builder $query, string $term, array $translatable, array $plain): void
    {
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        foreach ($translatable as $column) {
            foreach (self::LOCALES as $locale) {
                $query->orWhereRaw(self::normalizedSql(self::jsonText($query, $column, $locale))." LIKE ? ESCAPE '!'", [$like]);
            }
        }

        foreach ($plain as $column) {
            $query->orWhereRaw(self::normalizedSql(self::wrap($query, $column))." LIKE ? ESCAPE '!'", [$like]);
        }
    }

    /** SQL text of one locale of a JSON column. */
    public static function jsonText(Builder $query, string $column, string $locale): string
    {
        $col = self::wrap($query, $column);
        $path = "'$.{$locale}'";

        return match ($query->getConnection()->getDriverName()) {
            'sqlite' => "json_extract({$col}, {$path})",
            'pgsql' => "({$col}::json->>'{$locale}')",
            default => "JSON_UNQUOTE(JSON_EXTRACT({$col}, {$path}))", // mysql, mariadb
        };
    }

    /** LOWER(REPLACE(…)) mirroring self::normalize() in SQL. */
    private static function normalizedSql(string $expression): string
    {
        $sql = "COALESCE({$expression}, '')";
        foreach ([...self::DIACRITICS, ...self::ALEF_VARIANTS] as $char) {
            $replacement = in_array($char, self::ALEF_VARIANTS, true) ? 'ا' : '';
            $sql = "REPLACE({$sql}, '{$char}', '{$replacement}')";
        }

        return "LOWER({$sql})";
    }

    private static function wrap(Builder $query, string $column): string
    {
        return $query->getQuery()->getGrammar()->wrap($query->qualifyColumn($column));
    }
}
