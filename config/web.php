<?php

use App\Http\Controllers\ServerController;

return [
    'pagination_size' => 10,

    'controllers' => [
        'servers' => ServerController::class,
    ],
];
