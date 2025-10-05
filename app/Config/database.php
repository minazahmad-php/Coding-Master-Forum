<?php

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'forum_db',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ],
    ],
    'migrations' => 'migrations',
    'redis' => [
        'client' => 'predis',
        'default' => [
            'host' => '127.0.0.1',
            'password' => null,
            'port' => 6379,
            'database' => 0,
        ],
    ],
];