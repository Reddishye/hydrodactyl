<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Debugbar Settings
    |--------------------------------------------------------------------------
    |
    | Debugbar is enabled/disabled by APP_DEBUG. In production it's always off.
    |
    */
    'enabled' => env('DEBUGBAR_ENABLED', env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Storage settings
    |--------------------------------------------------------------------------
    |
    | DebugBar stores data for session/ajax requests.
    |
    */
    'storage' => [
        'enabled'    => true,
        'driver'     => 'file',
        'path'      => storage_path('debugbar'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Include Vendors
    |--------------------------------------------------------------------------
    |
    | Include vendor files in debug bar (e.g. mail, logs, etc.)
    |
    */
    'include_vendors' => true,

    /*
    |--------------------------------------------------------------------------
    | Capture Ajax Requests
    |--------------------------------------------------------------------------
    |
    | The Debugbar can capture Ajax requests and display them. If you don't
    | want this (e.g. due to performance), set to false.
    |
    */
    'capture_ajax' => true,

    /*
    |--------------------------------------------------------------------------
    | Data Collectors
    |--------------------------------------------------------------------------
    |
    | Enable/disable DataCollectors
    |
    */
    'collectors' => [
        'phpinfo'         => true,
        'messages'        => true,
        'time'            => true,
        'memory'          => true,
        'exceptions'      => true,
        'log'             => true,
        'db'              => true,
        'views'           => true,
        'route'           => true,
        'auth'            => true,
        'gate'            => true,
        'session'         => true,
        'symfony_request' => true,
        'mail'            => true,
        'php'             => false,
        'events'          => false,
        'default_request' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Extra DebugBar options
    |--------------------------------------------------------------------------
    */
    'options' => [
        'auth' => ['show_role' => true],
        'db' => [
            'with_params'       => true,
            'backtrace'         => true,
            'timeline'          => false,
            'slow_threshold'    => 200,
        ],
        'mail' => [
            'full_log' => true,
        ],
        'views' => [
            'timeline' => false,
            'data' => false,
        ],
        'route' => [
            'label' => true,
        ],
        'session' => [
            'hiddens' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inject Debugbar in Response
    |--------------------------------------------------------------------------
    |
    | Usually, the debugbar is automatically added to HTML responses. You can
    | disable this if you want to manually add it.
    |
    */
    'inject' => true,

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Debugbar adds a route with the Debugbar. You can disable this if needed.
    |
    */
    'route_prefix' => '_debugbar',
];
