<?php

return [
    'enabled' => filter_var(env('VITO_DESKTOP', false), FILTER_VALIDATE_BOOL),
    'data_path' => env('VITO_DATA_PATH'),
    'env_path' => env('VITO_ENV_PATH'),
    'storage_path' => env('VITO_STORAGE_PATH'),
    'build_directory' => 'build-desktop',
];
