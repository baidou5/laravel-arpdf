<?php

return [

    'direction'   => 'rtl',

    'default_font' => 'cairo',

    'temp_dir'    => storage_path('app/laravel-arpdf'),

    'fonts_path'  => resource_path('fonts/arpdf'),

    'fonts' => [
        'cairo' => [
            'R'         => 'Cairo-Regular.ttf',
            'B'         => 'Cairo-Bold.ttf',
            'useOTL'    => 0xFF,
            'useKashida'=> 75,
        ],
    ],
];
