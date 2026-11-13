<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\ContentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API v1 (consumed by the Next.js frontend) — see docs/API.md
|--------------------------------------------------------------------------
|
| GET limit uses a named counter ("api") so it never shares the contact counter.
| Contact uses the "contact" rate limiter (AppServiceProvider): per visitor IP via
| X-Client-IP when the site key is valid — see App\Support\ClientIp.
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::middleware(['locale', 'site.key', 'throttle:120,1,api'])
        ->controller(ContentController::class)
        ->group(function (): void {
            Route::get('site', 'site')->name('site');
            Route::get('home', 'home')->name('home');
            Route::get('pages/{key}', 'page')->name('pages.show');
            Route::get('service-categories', 'categories')->name('service-categories.index');
            Route::get('service-categories/{slug}', 'category')->name('service-categories.show');
            Route::get('sitemap', 'sitemap')->name('sitemap');
        });

    // Called server-side by Next.js; browsers are limited by CORS (config/cors.php).
    Route::post('contact', ContactController::class)
        ->middleware(['locale', 'throttle:contact'])
        ->name('contact');
});
