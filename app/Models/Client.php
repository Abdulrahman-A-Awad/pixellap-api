<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasWebpMedia;
use App\Models\Concerns\IsContent;
use App\Models\Concerns\IsSortable;
use App\Support\MimeTypes;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\MediaLibrary\HasMedia;

class Client extends Model implements HasMedia, Sortable
{
    use HasWebpMedia;
    use IsContent;
    use IsSortable;

    protected $fillable = ['name', 'url', 'sort', 'is_visible'];

    /**
     * Client names are proper nouns and are not translated.
     *
     * @var list<string>
     */
    public array $translatable = [];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile()->acceptsMimeTypes(MimeTypes::LOGOS);
    }

    /** SVG logos are skipped automatically by HasWebpMedia. */
    protected function webpCollections(): array
    {
        return ['logo'];
    }
}
