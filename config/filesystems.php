<?php


return [
    'default' => 'local',
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => getcwd(),
        ],
        'home' => [
            'driver' => 'local',
            'root' => $_SERVER['HOME'],
        ],
        'internal' => [//phar and local
            'driver' => 'local',
            'root' => base_path(),
        ],
    ],
];
