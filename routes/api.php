<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API v1 (read-only, consumed by the Next.js frontend)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')
    ->middleware(['site.key', 'locale'])
    ->name('api.v1.')
    ->group(function (): void {
        Route::get('ping', fn () => ['status' => 'ok', 'locale' => app()->getLocale()])->name('ping');
    });
