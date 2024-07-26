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
            'root' => (isset($_SERVER['HOME']) ? $_SERVER['HOME'] : $_SERVER['USERPROFILE']).'/.dexor',
        ],
        'root' => [
            'driver' => 'local',
            'root' => isset($_SERVER['HOME']) ? $_SERVER['HOME'] : $_SERVER['USERPROFILE'],
        ],
        'internal' => [//phar and local
            'driver' => 'local',
            'root' => base_path(),
        ],
    ],
];
