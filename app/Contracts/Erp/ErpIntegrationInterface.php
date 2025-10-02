<?php

namespace App\Contracts\Erp;

use App\Models\Integration;

interface ErpIntegrationInterface
{
    public function authenticate(): bool;
    public function getIntegration(): Integration;
    public function getAuthHandler(): ErpAuthInterface;
}