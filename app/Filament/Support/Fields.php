<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Support\MimeTypes;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;

/**
 * Field factories that apply project-wide admin conventions:
 * - translatable text inputs type in the right direction (dir="auto")
 * - uploads use media library collections with consistent limits
 */
final class Fields
{
    public const IMAGE_MAX_KB = 10 * 1024;

    public const VIDEO_MAX_KB = 40 * 1024;

    /** Translatable single-line text. */
    public static function text(string $name): TextInput
    {
        return TextInput::make($name)
            ->maxLength(255)
            ->extraInputAttributes(['dir' => 'auto']);
    }

    /** Translatable multi-line text. */
    public static function textarea(string $name, int $rows = 3): Textarea
    {
        return Textarea::make($name)
            ->rows($rows)
            ->autosize()
            ->extraInputAttributes(['dir' => 'auto']);
    }

    /** Translatable rich text with a limited toolbar. */
    public static function rich(string $name): RichEditor
    {
        return RichEditor::make($name)
            ->toolbarButtons(['bold', 'italic', 'link', 'bulletList'])
            ->extraInputAttributes(['dir' => 'auto']);
    }

    /**
     * EN + AR inputs side by side writing {name}.en / {name}.ar — used for EVERY translatable field
     * (no language switcher: an empty Arabic value used to show the English text silently).
     * English is required when `$required`; an empty Arabic value only shows a warning hint.
     *
     * `$tap` customises each input: fn (Field $field, string $locale): void (e.g. a max length, a slug generator).
     *
     * @param  'text'|'textarea'|'rich'|'tags'  $kind  tags = a list of strings per language (e.g. service features)
     * @param  ?\Closure(Field, string): void  $tap
     */
    public static function bilingual(
        string $name,
        string $label,
        string $kind = 'text',
        ?string $help = null,
        bool $required = false,
        int $maxLength = 500,
        ?\Closure $tap = null,
        int $rows = 3,
    ): Grid {
        $make = fn (string $locale): Field => match ($kind) {
            'textarea' => Textarea::make("{$name}.{$locale}")->rows($rows)->autosize(),
            'rich' => RichEditor::make("{$name}.{$locale}")->toolbarButtons(['bold', 'italic', 'link', 'bulletList']),
            'tags' => TagsInput::make("{$name}.{$locale}")->reorderable()->placeholder(__('Type and press Enter')),
            default => TextInput::make("{$name}.{$locale}")->maxLength($maxLength),
        };

        $en = $make('en')
            ->label(__($label).' — English')
            ->helperText($help)
            ->required($required)
            ->validationAttribute(__($label).' (English)')
            ->extraInputAttributes(['dir' => 'ltr', 'lang' => 'en']);

        $ar = $make('ar')
            ->label(__($label).' — العربية')
            ->live(onBlur: true)
            // Not blocking: the website falls back to English, but editors should see the gap.
            ->hint(fn (mixed $state, Get $get): ?string => self::arabicMissing($state, $get("{$name}.en")) ? __('Arabic missing') : null)
            ->hintIcon(fn (mixed $state, Get $get): ?string => self::arabicMissing($state, $get("{$name}.en")) ? 'heroicon-m-exclamation-triangle' : null)
            ->hintColor('warning')
            ->extraInputAttributes(['dir' => 'rtl', 'lang' => 'ar']);

        if ($tap !== null) {
            $tap($en, 'en');
            $tap($ar, 'ar');
        }

        return Grid::make(['default' => 1, 'md' => 2])->schema([$en, $ar]);
    }

    /** Arabic is empty while English has text (both empty = nothing to translate yet). Lists: empty AR list vs non-empty EN. */
    public static function arabicMissing(mixed $ar, mixed $en): bool
    {
        $blank = fn (mixed $v): bool => is_array($v)
            ? array_filter($v, fn ($item): bool => trim(strip_tags((string) (is_scalar($item) ? $item : ''))) !== '') === []
            : trim(strip_tags((string) (is_string($v) ? $v : ''))) === '';

        return $blank($ar) && ! $blank($en);
    }

    /** Raster image upload with editor + preview. */
    public static function image(string $collection): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($collection)
            ->collection($collection)
            ->image()
            ->imageEditor()
            ->acceptedFileTypes(MimeTypes::IMAGES)
            ->maxSize(self::IMAGE_MAX_KB)
            ->downloadable();
    }

    /** SVG / PNG / WebP upload (logos, icons) — no editor, SVG cannot be edited. */
    public static function vectorOrImage(string $collection, array $types = MimeTypes::LOGOS): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($collection)
            ->collection($collection)
            ->acceptedFileTypes($types)
            ->maxSize(self::IMAGE_MAX_KB)
            ->downloadable();
    }

    public static function video(string $collection): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($collection)
            ->collection($collection)
            ->acceptedFileTypes(MimeTypes::VIDEOS)
            ->maxSize(self::VIDEO_MAX_KB)
            ->downloadable()
            ->helperText(__('MP4 or WebM, up to 40 MB.'));
    }

    public static function visible(): Toggle
    {
        return Toggle::make('is_visible')
            ->label('Visible on website')
            ->helperText(__('Turn off to hide it on the website without deleting it.'))
            ->default(true);
    }
}
