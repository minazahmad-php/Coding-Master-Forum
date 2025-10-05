<?php

return [
    'default' => 'file',
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache'),
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],
        'memcached' => [
            'driver' => 'memcached',
            'servers' => [
                [
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 100,
                ],
            ],
        ],
    ],
    'prefix' => 'forum_cache',
    'ttl' => 3600, // 1 hour default TTL
];