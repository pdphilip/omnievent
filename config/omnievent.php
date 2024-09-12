<?php

return [
    'database' => 'elasticsearch',

    'queue' => null, //Set queue to use for dispatching index builds, ex: default, high, low, etc.

    'throw_exceptions' => true,

    'namespaces' => [
        'models' => 'App\Models',
        'events' => 'App\Models\Events',
    ],

    'app_paths' => [
        'models' => 'Models/',
        'events' => 'Models/Events/',
    ],
];
