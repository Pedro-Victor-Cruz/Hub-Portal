<?php

use App\Enums\IntegrationType;
use App\Services\Integrations\Protheus\ProtheusIntegration;
use App\Services\Integrations\Sankhya\SankhyaIntegration;

return [
    'drivers' => [
        IntegrationType::SANKHYA->value => SankhyaIntegration::class,
        IntegrationType::TOTVS->value => ProtheusIntegration::class,
    ],

];