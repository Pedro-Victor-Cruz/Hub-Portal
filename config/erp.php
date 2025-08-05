<?php

use App\Services\Erp\Drivers\SankhyaDriver;

return [
    'drivers' => [
        'SANKHYA' => SankhyaDriver::class,
    ],
    'erp'     => [
        'settings' => [
            'SANKHYA' => [
                'appkey' => '',
                'token'  => '',
            ],
        ]
    ]
];