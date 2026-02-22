<?php

return [
    'database' => 'elasticsearch',

    'throw_exceptions' => true,

    'save_request' => true,

    'namespaces' => [
        'models' => 'App\Models',
        'events' => 'App\Models\Events',
    ],

    'app_paths' => [
        'models' => 'Models/',
        'events' => 'Models/Events/',
    ],
];
