<?php

use App\Enums\IntegrationType;
use App\Services\Integrations\Protheus\ProtheusIntegration;
use App\Services\Integrations\Sankhya\SankhyaIntegration;
use App\Services\Integrations\Vtex\VtexIntegration;

return [
    'drivers' => [
        IntegrationType::SANKHYA->value => SankhyaIntegration::class,
        IntegrationType::TOTVS->value => ProtheusIntegration::class,
        IntegrationType::VTEX->value => VtexIntegration::class,
    ],

];