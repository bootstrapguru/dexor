<?php

return [
    'paths' => [
        resource_path('views'),
    ],

    'compiled' => \Phar::running()
        ? getcwd()
        : env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))),
];
