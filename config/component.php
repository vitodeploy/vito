<?php

return [
    'runtimes' => [],
    'capabilities' => [],
    'port_range' => [
        'min' => 10000,
        'max' => 19999,
    ],
    'deploy' => [
        'stage_timeouts' => [
            'clone' => 600,
            'build' => 1800,
            'pre-release' => 1800,
            'release' => 300,
            'post-release' => 900,
        ],
    ],
];
