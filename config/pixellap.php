<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Frontend (Next.js)
    |--------------------------------------------------------------------------
    */

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

    'frontend_revalidate_url' => env('FRONTEND_REVALIDATE_URL'),

    'revalidate_secret' => env('REVALIDATE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    |
    | Shared key the frontend sends in the X-Site-Key header on GET requests.
    |
    */

    'api_site_key' => env('API_SITE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'contact_notify_email' => env('CONTACT_NOTIFY_EMAIL'),

];
