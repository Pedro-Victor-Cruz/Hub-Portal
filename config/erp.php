<?php

use App\Services\Erp\Drivers\Sankhya\SankhyaDriver;

return [
    'drivers' => [
        'SANKHYA' => SankhyaDriver::class,
    ],
    'settings' => [
        'SANKHYA' => [
            'appkey' => 'bd1213f6-e74d-488f-a73b-968010db2b25',
            'username' => '01465039619',
            'password' => 'Planejar2024@',
        ],
    ]
];