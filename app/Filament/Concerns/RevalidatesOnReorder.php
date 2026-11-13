<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Services\RevalidationService;
use App\Support\CacheTags;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament saves a new table order with a direct query update, which fires no
 * model events. Use this on every reorderable List page / relation manager so a
 * reorder still revalidates the website and is recorded in the activity log.
 */
trait RevalidatesOnReorder
{
    public function reorderTable(array $order): void
    {
        parent::reorderTable($order);

        if ($order === [] || ! $this->getTable()->isReorderable()) {
            return;
        }

        $modelClass = $this->getTable()->getModel();
        $records = $modelClass::query()->whereKey($order)->get();

        if ($records->isEmpty()) {
            return;
        }

        app(RevalidationService::class)->touch(
            $records->flatMap(fn (Model $record): array => CacheTags::for($record))->unique()->values()->all(),
        );

        $owner = method_exists($this, 'getOwnerRecord') ? $this->getOwnerRecord() : null;

        activity('content')
            ->causedBy(auth()->user())
            ->performedOn($owner ?? $records->first())
            ->event('reordered')
            ->withProperties(['attributes' => [
                'model' => class_basename($modelClass),
                'order' => array_map('intval', array_values($order)),
            ]])
            ->log('reordered');
    }
}
