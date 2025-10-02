<?php

use App\Enums\IntegrationType;
use App\Services\Erp\Sankhya\SankhyaIntegration;

return [
    'drivers' => [
        IntegrationType::SANKHYA->value => SankhyaIntegration::class,
    ],

];