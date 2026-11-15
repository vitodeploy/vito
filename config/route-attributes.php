<?php

use Illuminate\Routing\Middleware\SubstituteBindings;

return [
    'enabled' => true,

    'directories' => [
        app_path('Http/Controllers') => [
            'prefix' => '',
            'middleware' => 'web',
            'patterns' => ['*Controller.php'],
            'not_patterns' => ['API/*'],
        ],
        app_path('Http/Controllers/API') => [
            'prefix' => '',
            'middleware' => 'api',
            'patterns' => ['*Controller.php'],
            'not_patterns' => [],
        ],
    ],

    'middleware' => [
        SubstituteBindings::class,
    ],

    'scope-bindings' => null,
];
