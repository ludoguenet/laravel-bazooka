<?php

return [
    'enabled' => env('BAZOOKA_ENABLED', false),
    'probability' => env('BAZOOKA_PROBABILITY', 0.2),
    'strategies' => [
        'latency' => [
            'enabled' => true,
            'min_delay' => 100,
            'max_delay' => 3000,
        ],
        'exception' => [
            'enabled' => true,
            'exceptions' => [
                \RuntimeException::class => 'Chaos Engineering Exception',
                \InvalidArgumentException::class => 'Random Chaos Exception',
            ],
        ],
    ],
];
