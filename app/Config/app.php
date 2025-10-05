<?php

return [
    'name' => 'My Forum',
    'url' => 'https://coding-master.infy.uk',
    'timezone' => 'Asia/Dhaka',
    'debug' => false,
    'maintenance' => false,
    'version' => '1.0.0',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:' . base64_encode(random_bytes(32)),
    'providers' => [
        'App\Providers\AppServiceProvider',
        'App\Providers\DatabaseServiceProvider',
        'App\Providers\AuthServiceProvider',
        'App\Providers\RouteServiceProvider',
    ],
    'aliases' => [
        'App' => 'App\Core\Application',
        'Config' => 'App\Core\Config',
        'DB' => 'App\Core\Database',
        'View' => 'App\Core\View',
        'Session' => 'App\Core\Session',
        'Logger' => 'App\Core\Logger',
        'Auth' => 'App\Core\Auth',
        'Router' => 'App\Core\Router',
    ],
];