<?php

return [

    'path' => app_path('Modules'),

    'auto_discovery' => true,

    'development' => env('MODULES_DEVELOPMENT', env('APP_DEBUG', false)),

    'cache' => env('MODULES_CACHE', true),

    'cache_key' => 'app.modules',

    'cache_ttl' => 3600,

    'namespace' => 'App\\Modules',

    'requirements' => [
        'php' => '8.5',
        'laravel' => '13.0',
    ],

    'enabled' => [],

    'assets' => [
        'path' => public_path('modules'),
        'url' => '/modules',
    ],

    'views' => [
        'namespace_prefix' => 'module',
    ],

    'translations' => [
        'namespace_prefix' => 'module',
    ],

    'external_paths' => [],

    'load_composer_modules' => env('MODULES_LOAD_COMPOSER', false),

];
