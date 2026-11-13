<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Validation (English)
|--------------------------------------------------------------------------
| Merged over Laravel's built-in English messages: only overrides and the
| public contact form's attribute names live here.
*/

return [

    'custom' => [
        'email' => [
            'email' => 'Please enter a valid email address.',
        ],
        'phone' => [
            'regex' => 'The phone number may only contain digits, spaces and + ( ) -.',
        ],
        'service' => [
            'exists' => 'Please choose a service from the list.',
        ],
        'message' => [
            'min' => 'Please tell us a little more — at least :min characters.',
        ],
    ],

    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
        'phone' => 'phone number',
        'company' => 'company',
        'service' => 'service',
        'message' => 'message',
        'locale' => 'language',
    ],

];
