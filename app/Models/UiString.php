<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsContent;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface strings for the frontend (nav labels, buttons, form labels...).
 * Looked up by key, so not sortable or hideable.
 */
class UiString extends Model
{
    use IsContent;

    protected $fillable = ['key', 'group', 'value'];

    /** @var list<string> */
    public array $translatable = ['value'];
}
