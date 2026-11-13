<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ToggleAdminLocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin UI language toggle (user menu item in the Filament panel).
Route::post('/admin/locale/toggle', ToggleAdminLocaleController::class)
    ->middleware(['auth'])
    ->name('admin.locale.toggle');
