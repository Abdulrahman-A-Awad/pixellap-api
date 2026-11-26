<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SwitchAdminLocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin UI language (the only switch: user menu in the Filament panel).
Route::post('/admin/locale/{locale}', SwitchAdminLocaleController::class)
    ->middleware(['auth'])
    ->whereIn('locale', ['en', 'ar'])
    ->name('admin.locale.switch');
