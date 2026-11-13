<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Support\MimeTypes;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

/**
 * Field factories that apply project-wide admin conventions:
 * - translatable text inputs type in the right direction (dir="auto")
 * - uploads use media library collections with consistent limits
 */
final class Fields
{
    public const IMAGE_MAX_KB = 5 * 1024;

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
     * EN + AR inputs side by side writing {name}.en / {name}.ar
     * (for places where editors should see both languages at once).
     */
    public static function bilingual(string $name, string $label, bool $multiline = false): Grid
    {
        $make = fn (string $locale) => $multiline
            ? Textarea::make("{$name}.{$locale}")->rows(3)->autosize()
            : TextInput::make("{$name}.{$locale}")->maxLength(500);

        return Grid::make(2)->schema([
            $make('en')
                ->label(__($label).' — English')
                ->extraInputAttributes(['dir' => 'ltr']),
            $make('ar')
                ->label(__($label).' — العربية')
                ->extraInputAttributes(['dir' => 'rtl']),
        ]);
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
            ->default(true);
    }
}
